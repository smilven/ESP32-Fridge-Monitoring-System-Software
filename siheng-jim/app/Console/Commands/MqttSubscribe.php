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

        // --- 1. 原子锁逻辑：防止同一瞬间重复写入 ---
        // 使用 ROM 地址作为锁，锁定 2 秒。如果 2 秒内有相同 ROM 的消息进来，直接跳过。
        $lockKey = "mqtt_lock_" . $rom;
        if (!Cache::add($lockKey, true, 2)) {
            Log::debug("Duplicate message ignored for ROM: {$rom}");
            return;
        }

        // 获取传感器信息
        $sensor = Sensor::where('rom_address', $rom)->first();
        if (!$sensor) {
            Log::warning("Sensor {$rom} not found in database.");
            return;
        }

        // 判定当前温度是否触发警报
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

            $currentIsAlerting = $alertType ? true : false;
            $shouldCreateNewLog = false;
            $temperatureTolerance = 3; // 温度变化阈值

            if (!$lastLog) {
                $shouldCreateNewLog = true;
            } else {
                $timeDiff = Carbon::parse($lastLog->recorded_at)->diffInMinutes($now);
                $tempDiff = abs($lastLog->temperature - $temp);
                
                // 判断状态是否切换 (正常 <-> 报警)
                $statusChanged = ($lastLog->alert_status != $currentIsAlerting);

                // 判定是否需要【新增】记录
                if ($statusChanged) {
                    $shouldCreateNewLog = true; // 情况 A: 状态切换了，必须开新记录
                } elseif ($timeDiff >= 60) {
                    $shouldCreateNewLog = true; // 情况 B: 距离上次记录超过 1 小时
                } elseif ($tempDiff >= $temperatureTolerance) {
                    $shouldCreateNewLog = true; // 情况 C: 温度波动超过 3 度
                }
            }

            // --- 2. 执行 TemperatureLog 写入或更新逻辑 ---
            if ($shouldCreateNewLog) {
                // 创建新记录
                $currentLog = TemperatureLog::create([
                    'sensor_id'        => $sensor->id,
                    'temperature'      => $temp,
                    'alert_status'     => $currentIsAlerting,
                    'duration_minutes' => 0, // 新记录初始时长为 0
                    'recorded_at'      => $now
                ]);
                Log::info("Created New Log for Sensor {$sensor->id}: {$temp}°F");
            } else {
                // --- 核心修改：如果不创建新纪录，则更新最后一条记录的时长 ---
                if ($lastLog) {
                    $startTime = Carbon::parse($lastLog->created_at);
                    // 使用该记录最初创建的时间 (created_at) 与当前时间对比计算时长
                    $duration = (int)abs($now->diffInMinutes($startTime));

                    $lastLog->update([
                        'temperature'      => $temp,
                        'recorded_at'      => $now,
                        'duration_minutes' => $duration // 更新已持续的时长
                    ]);
                    $currentLog = $lastLog;
                } else {
                    $currentLog = null;
                }
            }

            // 3. 始终更新 TemperatureLatest (供前端实时查看)
            TemperatureLatest::updateOrCreate(
                ['sensor_id' => $sensor->id],
                [
                    'temperature'  => $temp,
                    'alert_status' => $currentIsAlerting,
                    'recorded_at'  => $now
                ]
            );

            // 4. 处理 Telegram 警报逻辑
            if ($currentLog) {
                $this->handleAlerts($sensor, $temp, $alertType, $currentLog);
            }

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