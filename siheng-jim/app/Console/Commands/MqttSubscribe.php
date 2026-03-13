<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use App\Models\Sensor;
use App\Models\TemperatureLog;
use App\Models\TemperatureLatest;
use App\Models\Alert;
use Carbon\Carbon;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:listen';

    protected $description = 'Listen fridge temperature sensors';

    public function handle()
    {

        $server   = env('MQTT_HOST', 'broker.emqx.io');
        $port     = env('MQTT_PORT', 1883);
        $clientId = 'laravel-temperature-listener';

        $mqtt = new MqttClient($server, $port, $clientId);

        $mqtt->connect();

        $this->info("MQTT Connected");

        $mqtt->subscribe('fridge/temperature', function ($topic, $message) {

            $data = json_decode($message, true);

            if (!$data) {
                return;
            }

            $rom  = $data['rom_address'] ?? null;
            $temp = $data['temperature'] ?? null;

            if (!$rom) {
                return;
            }

            $sensor = Sensor::where('rom_address', $rom)->first();

            if (!$sensor) {
                return;
            }

            /*
            --------------------------------
            判断 Alert 类型
            --------------------------------
            */

            $alertType = null;

            if ($sensor->max_temp && $temp > $sensor->max_temp) {
                $alertType = 'HIGH_TEMP';
            }

            if ($sensor->min_temp && $temp < $sensor->min_temp) {
                $alertType = 'LOW_TEMP';
            }

            /*
            --------------------------------
            存历史 TemperatureLog
            --------------------------------
            */

            $log = TemperatureLog::create([
                'sensor_id'   => $sensor->id,
                'temperature' => $temp,
                'alert_status'=> $alertType ? true : false,
                'recorded_at' => Carbon::now()
            ]);

            /*
            --------------------------------
            创建 / 更新 Alert
            --------------------------------
            */

            if ($alertType) {

                Alert::updateOrCreate(

                    [
                        'sensor_id' => $sensor->id,
                        'status' => 'Active'
                    ],

                    [
                        'temperature_log_id' => $log->id,
                        'alert_type' => $alertType,
                        'message' => 'Temperature '.$temp.' is out of range'
                    ]

                );

            } else {

                Alert::where('sensor_id', $sensor->id)
                    ->where('status', 'Active')
                    ->update([
                        'status' => 'Resolved',
                        'resolved_at' => now()
                    ]);

            }

            /*
            --------------------------------
            更新最新 TemperatureLatest
            --------------------------------
            */

            TemperatureLatest::updateOrCreate(
                ['sensor_id' => $sensor->id],
                [
                    'temperature' => $temp,
                    'alert_status'=> $alertType ? true : false,
                    'recorded_at' => Carbon::now()
                ]
            );

        }, 0);

        $mqtt->loop(true);
    }
}