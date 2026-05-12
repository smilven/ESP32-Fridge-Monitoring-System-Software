<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use App\Models\Sensor;
use App\Models\TemperatureLog;
use App\Models\TemperatureLatest;
use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen to fridge temperature sensors via MQTT with optimized storage logic';

    public function handle()
    {
        $server   = env('MQTT_HOST', 'broker.emqx.io');
        $port     = env('MQTT_PORT', 1883);
        $clientId = 'laravel-temperature-listener-' . uniqid();

        try {
            $mqtt = new MqttClient($server, $port, $clientId);
            $mqtt->connect();
            $this->info("Successfully connected to MQTT Broker: {$server}");
            Log::info("MQTT Listener started.");

            $mqtt->subscribe('+/temp', function ($topic, $message) {
                $this->processMessage($message);
            }, 0);

            $mqtt->loop(true);
        } catch (\Exception $e) {
            $this->error("MQTT Error: " . $e->getMessage());
            Log::error("MQTT Connection Failed: " . $e->getMessage());
        }
    }

    private function processMessage($message)
    {
        Log::debug("Received MQTT Payload: " . $message);

        $data = json_decode($message, true);
        if (!$data) {
            Log::error("Invalid JSON received: " . $message);
            return;
        }

        $rom  = $data['rom_address'] ?? null;
        $temp = $data['temperature'] ?? null;

        if (!$rom || $temp === null) {
            Log::warning("Incomplete payload. ROM: {$rom}, Temp: {$temp}");
            return;
        }

        // 原子锁：防止同一瞬间重复写入
        $lockKey = "mqtt_lock_" . $rom;
        if (!Cache::add($lockKey, true, 2)) {
            Log::debug("Duplicate message ignored for ROM: {$rom}");
            return;
        }

        $sensor = Sensor::where('rom_address', $rom)->first();
        if (!$sensor) {
            Log::warning("Sensor {$rom} not found in database.");
            return;
        }

        // ✅ 使用动态阈值（Schedule 优先，没有 Schedule 则用 Default）
        $thresholds   = $sensor->getCurrentThresholds();
        $minThreshold = $thresholds['min'];
        $maxThreshold = $thresholds['max'];
        $currentMode  = $thresholds['name'];

        Log::debug("Sensor {$rom} using threshold [{$currentMode}]: min={$minThreshold}, max={$maxThreshold}");

        // 判定警报类型
        $alertType = null;
        if ($maxThreshold !== null && $temp > $maxThreshold) {
            $alertType = 'HIGH_TEMP';
        } elseif ($minThreshold !== null && $temp < $minThreshold) {
            $alertType = 'LOW_TEMP';
        }

        $currentIsAlerting = (bool) $alertType;

        try {
            $now  = Carbon::now();
            $lastLog = TemperatureLog::where('sensor_id', $sensor->id)
                ->latest('recorded_at')
                ->first();

            $shouldCreateNewLog  = false;
            $temperatureTolerance = 5;

            if (!$lastLog) {
                $shouldCreateNewLog = true;
            } else {
                $startTime           = Carbon::parse($lastLog->created_at);
                $timeSinceFirstEntry = $startTime->diffInMinutes($now);
                $timeDiff            = abs($lastLog->temperature - $temp);
                $statusChanges       = ($lastLog->alert_status != $currentIsAlerting);

                if ($statusChanges) {
                    $shouldCreateNewLog = true;
                } elseif ($timeSinceFirstEntry >= 240) {
                    $shouldCreateNewLog = true;
                } elseif (!$currentIsAlerting && $timeDiff >= $temperatureTolerance) {
                    $shouldCreateNewLog = true;
                }
            }

            if ($shouldCreateNewLog) {
                $currentLog = TemperatureLog::create([
                    'sensor_id'        => $sensor->id,
                    'temperature'      => $temp,
                    'alert_status'     => $currentIsAlerting,
                    'duration_minutes' => 0,
                    'recorded_at'      => $now,
                ]);
                Log::info("Created New Log for Sensor {$sensor->id}: {$temp}°F (Mode: {$currentMode})");
            } else {
                if ($lastLog && $lastLog->alert_status == $currentIsAlerting) {
                    $startTime = Carbon::parse($lastLog->created_at);
                    $duration  = (int) abs($now->diffInMinutes($startTime));

                    $lastLog->update([
                        'temperature'      => $temp,
                        'recorded_at'      => $now,
                        'duration_minutes' => $duration,
                    ]);
                    $currentLog = $lastLog;
                } else {
                    $currentLog = null;
                }
            }

            // 始终更新 TemperatureLatest
            TemperatureLatest::updateOrCreate(
                ['sensor_id' => $sensor->id],
                [
                    'temperature'  => $temp,
                    'alert_status' => $currentIsAlerting,
                    'recorded_at'  => $now,
                ]
            );

            if ($currentLog) {
                $this->handleAlerts($sensor, $temp, $alertType, $currentLog, $currentMode);
            }

        } catch (\Exception $e) {
            Log::error("Database Operation Error: " . $e->getMessage());
        }
    }

    private function handleAlerts($sensor, $temp, $alertType, $log, $currentMode = 'Default')
    {
        if (!$log) return;

        $token         = config('services.telegram.bot_token');
        $chat_id       = config('services.telegram.chat_id');
        $qa_chat_id    = config('services.telegram.qa_id');
        $tech_group_id = config('services.telegram.tech_group_id');
        $outletName    = optional($sensor->device->fridge->branch)->name ?? 'Unknown';

        if ($alertType) {
            $alert = Alert::where('sensor_id', $sensor->id)
                ->where('status', 'Active')
                ->first();

            if (!$alert) {
                $alert = Alert::create([
                    'sensor_id'           => $sensor->id,
                    'temperature_log_id'  => $log->id,
                    'alert_type'          => $alertType,
                    'message'             => "Temp {$temp}°F out of range ({$currentMode})",
                    'outlet_name'         => $outletName,
                    'status'              => 'Active',
                    'escalated'           => false,
                    'reported'            => false,
                ]);

                $this->sendTelegram(
                    $token, $chat_id,
                    "🚨 <b>FRIDGE ALERT</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F\nOutlet: {$outletName}\nMode: {$currentMode}\nType: {$alertType}",
                    $alert->id
                );
            } else {
                if (!$alert->reported && $alert->updated_at->diffInMinutes(now()) >= 15) {
                    $this->sendTelegram(
                        $token, $chat_id,
                        "⚠️ <b>STILL ACTIVE</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F\nMode: {$currentMode}\nOutlet: {$outletName}",
                        $alert->id
                    );
                    $alert->touch();
                }

                if ($alert->created_at->diffInMinutes(now()) >= 15 && !$alert->escalated) {
                    $this->sendTelegram(
                        $token, $qa_chat_id,
                        "🚨 <b>ESCALATION</b>\nSensor: {$sensor->rom_address}\nOutlet: {$outletName}\nMode: {$currentMode}\nImmediate action required!"
                    );
                    $alert->update(['escalated' => true]);
                }
            }
        } else {
            $activeAlert = Alert::where('sensor_id', $sensor->id)->where('status', 'Active')->first();
            if ($activeAlert) {
                $activeAlert->update(['status' => 'Resolved', 'resolved_at' => now()]);

                $msg = "✅ <b>RESOLVED</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F\nOutlet: {$outletName}\nMode: {$currentMode}";

                $this->sendTelegram($token, $chat_id, $msg);

                if ($activeAlert->escalated) {
                    $this->sendTelegram($token, $qa_chat_id, $msg);
                }
                if ($activeAlert->reported) {
                    $this->sendTelegram($token, $tech_group_id, $msg);
                }
            }
        }
    }

    private function sendTelegram($token, $chatId, $text, $alertId = null)
    {
        if (empty($token) || empty($chatId)) return;

        $params = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];

        if ($alertId) {
            $params['reply_markup'] = json_encode([
                'inline_keyboard' => [[['text' => '🛠 Report Issue', 'callback_data' => 'report_' . $alertId]]]
            ]);
        }

        try {
            Http::timeout(5)->get("https://api.telegram.org/bot{$token}/sendMessage", $params);
        } catch (\Exception $e) {
            Log::error("Telegram Send Failed: " . $e->getMessage());
        }
    }
}