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
    $data = json_decode($message, true);
    if (!$data) return;

    $rom  = $data['rom_address'] ?? null;
    $temp = $data['temperature'] ?? null;

    if (!$rom || $temp === null) return;

    // --- 1. 并发防抖锁定 (防止同一瞬间双写) ---
    // 使用 ROM 地址作为锁的 Key，锁定 2 秒，防止 MQTT 重复发送
    $lockKey = "mqtt_lock_" . $rom;
    if (!Cache::add($lockKey, true, 2)) {
        Log::debug("Duplicate message ignored for ROM: {$rom}");
        return;
    }

    // 获取传感器
    $sensor = Sensor::where('rom_address', $rom)->first();
    if (!$sensor) return;

    // 判定报警类型
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
        $temperatureTolerance = 3; 

        if (!$lastLog) {
            $shouldCreateNewLog = true;
        } else {
            $timeDiff = Carbon::parse($lastLog->recorded_at)->diffInMinutes($now);
            $tempDiff = abs($lastLog->temperature - $temp);
            $statusChanged = ($lastLog->alert_status != $currentIsAlerting);

            // --- 2. 存储判定逻辑优化 ---
            if ($statusChanged) {
                // 只要状态变了（正常->报警 或 报警->正常），必须存新的一条
                $shouldCreateNewLog = true; 
            } elseif ($timeDiff >= 60 || $tempDiff >= $temperatureTolerance) {
                // 或者时间太久了，或者温度波动太大，才存新的
                $shouldCreateNewLog = true;
            }
            // 如果上述都不符合，说明状态没变，温度也稳定，就不新增记录
        }

        // --- 3. 执行存储或更新 ---
        if ($shouldCreateNewLog) {
            $currentLog = TemperatureLog::create([
                'sensor_id'    => $sensor->id,
                'temperature'  => $temp,
                'alert_status' => $currentIsAlerting,
                'recorded_at'  => $now
            ]);
            Log::info("Created New Log for Sensor {$sensor->id}: {$temp}°F");
        } else {
            // --- 你的需求：报警中或平时不满足条件时，更新最后一条记录的时间和温度，不新增 ---
            if ($lastLog) {
                $lastLog->update([
                    'temperature' => $temp,
                    'recorded_at' => $now
                ]);
                $currentLog = $lastLog;
            }
        }

        // 4. 始终更新实时表
        TemperatureLatest::updateOrCreate(
            ['sensor_id' => $sensor->id],
            [
                'temperature'  => $temp,
                'alert_status' => $currentIsAlerting,
                'recorded_at'  => $now
            ]
        );

        // 5. 处理警报
        $this->handleAlerts($sensor, $temp, $alertType, $currentLog);

    } catch (\Exception $e) {
        Log::error("Database Error: " . $e->getMessage());
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