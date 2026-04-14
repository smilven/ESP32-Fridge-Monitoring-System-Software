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
        $tempTolerance = 5;
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

        $currentIsAlerting = $alertType ? true : false;

        try {
            $now = Carbon::now();
            $lastLog = TemperatureLog::where('sensor_id', $sensor->id)
                ->latest('recorded_at')
                ->first();

            $currentIsAlerting = $alertType ? true : false;
            $shouldCreateNewLog = false;
            $temperatureTolerance = 5; // 温度变化阈值

            if(!$lastLog) {
                // 没有历史记录，必须创建
                $shouldCreateNewLog = true;
            } else {
             $startTime = Carbon::parse($lastLog->created_at);
             $timeSinceFirstEntry = $startTime->diffInMinutes($now);
             
             $timDiff =abs($lastLog->temperature - $temp);
             $statusChanges =($lastLog->alert_status != $currentIsAlerting);

             if($statusChanges){
                $shouldCreateNewLog = true;
             }elseif($timeSinceFirstEntry >= 240){
                // 已经持续了240分钟，强制创建新记录
                $shouldCreateNewLog = true;
             }elseif(!$currentIsAlerting && $timDiff >= $temperatureTolerance){   
                // 温度变化超过阈值，创建新记录 (仅限于从正常变为正常的情况，避免频繁记录警报状态的微小波动)
                 $shouldCreateNewLog = true;
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
                if ($lastLog && $lastLog->alert_status == $currentIsAlerting) {
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

        $token = config('services.telegram.bot_token');
        $chat_id = config('services.telegram.chat_id');
        $qa_chat_id = config('services.telegram.qa_id');
        $tech_group_id = config('services.telegram.tech_group_id');
        $outletName = optional($sensor->device->fridge->branch)->name ?? 'Unknown';
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
                    'outlet_name' => $outletName,
                    'status' => 'Active',
                    'escalated' => false,
                    'reported' => false
                ]);

                $this->sendTelegram($token, $chat_id, "🚨 <b>FRIDGE ALERT</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F\nOutlet: {$outletName}\nType: {$alertType}", $alert->id);
            } else {
                // 报警持续中的冷却逻辑 (15分钟发一次，且如果已经被 report 了就不再发)
                if (!$alert->reported && $alert->updated_at->diffInMinutes(now()) >= 15) {
                    $this->sendTelegram($token, $chat_id, "⚠️ <b>STILL ACTIVE</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F\nOutlet: {$outletName}", $alert->id);
                    $alert->touch(); 
                }

                // 升级逻辑 (15分钟没解决，发去大群)
                if ($alert->created_at->diffInMinutes(now()) >= 15 && !$alert->escalated) {
                    $this->sendTelegram($token, $qa_chat_id, "🚨 <b>ESCALATION</b>\nSensor: {$sensor->rom_address}\nOutlet: {$outletName}\nImmediate action required!");
                    $alert->update(['escalated' => true]);
                }
            }
        } else {
            // 温度恢复正常，关闭警报
            $activeAlert = Alert::where('sensor_id', $sensor->id)->where('status', 'Active')->first();
            if ($activeAlert) {
                $activeAlert->update(['status' => 'Resolved', 'resolved_at' => now()]);
                
                $msg = "✅ <b>RESOLVED</b>\nSensor: {$sensor->rom_address}\nTemp: {$temp}°F\nOutlet: {$outletName}";
                
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