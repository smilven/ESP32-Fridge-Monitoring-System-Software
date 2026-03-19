<?php

namespace App\Console\Commands;

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

            $mqtt->subscribe('fridge/temperature', function ($topic, $message) {
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

        // 1. 获取传感器信息
        $sensor = Sensor::where('rom_address', $rom)->first();
        if (!$sensor) {
            Log::warning("Sensor {$rom} not found in database.");
            return;
        }

        // 2. 判定当前温度是否触发警报
        $alertType = null;
        if ($sensor->max_temp && $temp > $sensor->max_temp) {
            $alertType = 'HIGH_TEMP';
        } elseif ($sensor->min_temp && $temp < $sensor->min_temp) {
            $alertType = 'LOW_TEMP';
        }

        try {
            $now = Carbon::now();
            $lastLog = TemperatureLog::where('sensor_id', $sensor->id)
                ->latest('recorded_at')
                ->first();

            $shouldStoreLog = false;
            $temperatureTolerance = 3; // 温度变化阈值

            if (!$lastLog) {
                $shouldStoreLog = true;
            } else {
                $timeDiff = Carbon::parse($lastLog->recorded_at)->diffInHours($now);
                $tempDiff = abs($lastLog->temperature - $temp);
                
                // 判断报警状态是否发生了切换 (关键：防止报警期间持续写入)
                $currentIsAlerting = $alertType ? true : false;
                $statusChanged = ($lastLog->alert_status != $currentIsAlerting);

                // 存储判定逻辑
                if ($timeDiff >= 6) {
                    $shouldStoreLog = true; // 情况 A: 时间到了
                } elseif ($tempDiff >= $temperatureTolerance) {
                    $shouldStoreLog = true; // 情况 B: 温度变化显著
                } elseif ($statusChanged) {
                    $shouldStoreLog = true; // 情况 C: 状态切换（刚报警或刚恢复）
                }
            }

            // 3. 执行 TemperatureLog 写入
            if ($shouldStoreLog) {
                $currentLog = TemperatureLog::create([
                    'sensor_id'    => $sensor->id,
                    'temperature'  => $temp,
                    'alert_status' => $alertType ? true : false,
                    'recorded_at'  => $now
                ]);
                Log::info("Stored Log for Sensor {$sensor->id}: {$temp}°F");
            } else {
                // 如果不存新 Log，则引用最后一条记录用于警报逻辑
                $currentLog = $lastLog;
            }

            // 4. 始终更新 TemperatureLatest (供前端实时查看)
            TemperatureLatest::updateOrCreate(
                ['sensor_id' => $sensor->id],
                [
                    'temperature'  => $temp,
                    'alert_status' => $alertType ? true : false,
                    'recorded_at'  => $now
                ]
            );

            // 5. 处理 Telegram 警报逻辑
            $this->handleAlerts($sensor, $temp, $alertType, $currentLog);

        } catch (\Exception $e) {
            Log::error("Database Operation Error: " . $e->getMessage());
        }
    }

    private function handleAlerts($sensor, $temp, $alertType, $log)
    {
        if (!$log) return;

        $token = env('TELEGRAM_BOT_TOKEN');
        $chat_id = env('TELEGRAM_CHAT_ID');
        $group_chat_id = env('TELEGRAM_GROUP_ID');
        $tech_group_id = env('TELEGRAM_TECH_GROUP_ID');

        if ($alertType) {
            $alert = Alert::where('sensor_id', $sensor->id)
                ->where('status', 'Active')
                ->first();

            if (!$alert) {
                // 新建警报记录
                $alert = Alert::create([
                    'sensor_id' => $sensor->id,
                    'temperature_log_id' => $log->id,
                    'alert_type' => $alertType,
                    'message' => "Temperature {$temp}°F is out of range",
                    'status' => 'Active',
                    'escalated' => false,
                    'reported' => false
                ]);

                $this->sendTelegram($token, $chat_id, "🚨 <b>FRIDGE ALERT</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F\nType: {$alertType}", $alert->id);
            } else {
                // 报警持续中的冷却逻辑 (60秒发一次，且如果已经被 report 了就不再发)
                if (!$alert->reported && $alert->updated_at->diffInSeconds(now()) >= 60) {
                    $this->sendTelegram($token, $chat_id, "⚠️ <b>STILL ACTIVE</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F", $alert->id);
                    $alert->touch(); 
                }

                // 升级逻辑 (5分钟没解决，发去大群)
                if ($alert->created_at->diffInMinutes(now()) >= 5 && !$alert->escalated) {
                    $this->sendTelegram($token, $group_chat_id, "🚨 <b>ESCALATION</b>\nSensor: {$sensor->rom_address}\nImmediate action required!");
                    $alert->update(['escalated' => true]);
                }
            }
        } else {
            // 温度恢复正常，关闭警报
            $activeAlert = Alert::where('sensor_id', $sensor->id)->where('status', 'Active')->first();
            if ($activeAlert) {
                $activeAlert->update(['status' => 'Resolved', 'resolved_at' => now()]);
                
                $msg = "✅ <b>RESOLVED</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F";
                
                $this->sendTelegram($token, $chat_id, $msg);

                if ($activeAlert->escalated) {
                    $this->sendTelegram($token, $group_chat_id, $msg);
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
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
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