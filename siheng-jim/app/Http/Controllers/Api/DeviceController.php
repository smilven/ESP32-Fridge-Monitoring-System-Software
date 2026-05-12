<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Sensor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'device_uid' => 'required|string',
            'serial_no'  => 'required|string',
        ]);

        $device = Device::where('device_uid', $request->device_uid)->first();

        if (!$device) {
            $device = Device::create([
                'device_uid'   => $request->device_uid,
                'serial_no'    => $request->serial_no,
                'mqtt_port'    => 1883,
                'mqtt_broker'  => 'broker.emqx.io',
                'mqtt_topic'   => $request->device_uid . '/temp',
                'device_token' => Str::random(40),
            ]);
        }

        return response()->json([
            'device_uid'   => $device->device_uid,
            'device_token' => $device->device_token,
        ]);
    }

    public function config(Request $request)
    {
        $device = Device::where('device_uid', $request->device_uid)
            ->where('device_token', $request->device_token)
            ->first();

        if (!$device) {
            return response()->json(['error' => 'device not found'], 401);
        }

        // ✅ 返回动态阈值：有 schedule 就用 schedule，否则用 sensor default
        $sensors = Sensor::where('device_id', $device->id)
            ->get()
            ->map(function (Sensor $sensor) {
                $thresholds = $sensor->getCurrentThresholds();
                return [
                    'rom_address' => $sensor->rom_address,
                    'min_temp'    => $thresholds['min'],
                    'max_temp'    => $thresholds['max'],
                    'mode'        => $thresholds['name'], // 方便 ESP32 debug 用
                ];
            });

        return response()->json([
            'mqtt_broker'   => $device->mqtt_broker ?? 'broker.emqx.io',
            'mqtt_port'     => $device->mqtt_port ?? 1883,
            'mqtt_topic'    => "{$device->device_uid}/temp",
            'mqtt_username' => $device->mqtt_username,
            'mqtt_password' => $device->mqtt_password,
            'sensors'       => $sensors,
        ]);
    }

    public function updateStatus(Request $request)
    {
        $device = Device::where('device_uid', $request->device_uid)
            ->where('device_token', $request->device_token)
            ->first();

        if (!$device) {
            return response()->json(['error' => 'not found'], 401);
        }

        $oldStatus    = $device->status;
        $device->status    = $request->status;
        $device->last_seen = now();
        $device->save();

        $token    = config('services.telegram.bot_token');
        $location = optional($device->fridge->branch)->name ?? 'Unknown';

        if ($request->status === 'error' && $oldStatus !== 'error') {
            $this->sendTelegram($token,
                "⚠️ <b>MQTT ERROR</b>\nDevice: {$device->serial_no}\nOutlet: {$location}\nTime: " . now()
            );
        }

        if ($request->status === 'online' && $oldStatus === 'error') {
            $this->sendTelegram($token,
                "❤️‍🩹 <b>DEVICE RECOVERED</b>\nDevice: {$device->serial_no}\nOutlet: {$location}\nTime: " . now()
            );
        }

        return response()->json(['message' => 'updated']);
    }

    public function heartbeat(Request $request)
    {
        $device = Device::where('device_uid', $request->device_uid)
            ->where('device_token', $request->device_token)
            ->first();

        if (!$device) {
            return response()->json(['error' => 'device not found'], 401);
        }

        $wasOffline = ($device->status === 'offline');

        if ($device->status !== 'error') {
            $device->status    = 'online';
            $device->last_seen = now();
            $device->save();
        }

        if ($wasOffline) {
            $location = optional($device->fridge->branch)->name ?? 'Unknown';
            $token    = config('services.telegram.bot_token');
            $this->sendTelegram($token,
                "🟢 <b>DEVICE ONLINE</b>\nDevice: {$device->serial_no}\nOutlet: {$location}\nTime: " . now()
            );
        }

        return response()->json(['message' => 'heartbeat received']);
    }

    public function registerSensor(Request $request)
    {
        $request->validate([
            'device_uid'   => 'required|string',
            'device_token' => 'required|string',
            'rom_address'  => 'required|string',
        ]);

        $device = Device::where('device_uid', $request->device_uid)
            ->where('device_token', $request->device_token)
            ->first();

        if (!$device) {
            return response()->json(['error' => 'device not found'], 401);
        }

        $sensor = Sensor::where('rom_address', $request->rom_address)->first();

        if (!$sensor) {
            $sensor = Sensor::create([
                'device_id'   => $device->id,
                'rom_address' => $request->rom_address,
            ]);
        }

        return response()->json([
            'message'   => 'sensor registered',
            'sensor_id' => $sensor->id,
        ]);
    }

    private function sendTelegram($token, $message)
    {
        Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => config('services.telegram.tech_group_id'),
            'text'       => $message,
            'parse_mode' => 'HTML',
        ]);
    }
}