<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Sensor;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function register(Request $request)
    {

        $request->validate([
            'device_uid' => 'required|string',
            'serial_no' => 'required|string',
        ]);

        $device = Device::where('device_uid', $request->device_uid)->first();

        if (!$device) {

            $device = Device::create([
                'device_uid' => $request->device_uid,
                'serial_no' => $request->serial_no,
                'mqtt_port' => 1883,
                'mqtt_broker' =>'broker.emqx.io',
                'mqtt_topic' => $request->device_uid . '/temp',
                'device_token' => Str::random(40),
            ]);
        }

        return response()->json([
            'device_uid' => $device->device_uid,
            'device_token' => $device->device_token
        ]);
    }


public function config(Request $request)
{

    $device = Device::where('device_uid', $request->device_uid)
        ->where('device_token', $request->device_token)
        ->first();

    if (!$device) {
        return response()->json([
            'error' => 'device not found'
        ], 401);
    }

    $sensors = Sensor::where('device_id', $device->id)
        ->get(['rom_address', 'min_temp', 'max_temp']);

    return response()->json([
        'mqtt_broker' => $device->mqtt_broker ?? 'broker.emqx.io',
        'mqtt_port'   => $device->mqtt_port ?? 1883,
        'mqtt_topic' => "{$device->device_uid}/temp",
        'mqtt_username' => $device->mqtt_username,
        'mqtt_password' => $device->mqtt_password,
        'sensors' => $sensors
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

    // ⭐ 记录旧状态
    $oldStatus = $device->status;

    // ⭐ 更新状态 + 心跳
    $device->status = $request->status;
    $device->last_seen = now();
    $device->save();

    $token = env('TELEGRAM_BOT_TOKEN');

    // 🚨 1. error（只发一次）
    if ($request->status === 'error' && $oldStatus !== 'error') {

        $message = "⚠️ <b>MQTT ERROR</b>\n"
            . "Device: {$device->serial_no}\n"
            . "Time: " . now();

        $this->sendTelegram($token, $message);
    }

    // 🟢 2. error → online（恢复）
    if ($request->status === 'online' && $oldStatus === 'error') {

        $message = "❤️‍🩹 <b>DEVICE RECOVERED</b>\n"
            . "Device: {$device->serial_no}\n"
            . "Time: " . now();

        $this->sendTelegram($token, $message);
    }

    return response()->json(['message' => 'updated']);
}

private function sendTelegram($token, $message)
{
    \Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);

    \Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
        'chat_id' => env('TELEGRAM_GROUP_ID'),
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);

    \Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
        'chat_id' => env('TELEGRAM_TECH_GROUP_ID'),
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);
}

public function heartbeat(Request $request)
{
    $device = Device::where('device_uid',$request->device_uid)
        ->where('device_token',$request->device_token)
        ->first();

    if(!$device){
        return response()->json([
            'error'=>'device not found'
        ],401);
    }

    $wasOffline = ($device->status === 'offline'); // ⭐ 关键

    if ($device->status !== 'error') {
        $device->status = 'online';
        $device->last_seen = now();
        $device->save();
    }

    // ✅ 只有 offline → online 才发送
    if ($wasOffline) {

        $token = env('TELEGRAM_BOT_TOKEN');

        $message = "🟢 <b>DEVICE ONLINE</b>\n"
            . "Device: {$device->serial_no}\n"
            . "Time: " . now();

        // Crew
        \Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => env('TELEGRAM_CHAT_ID'),
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        // QA
        \Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => env('TELEGRAM_GROUP_ID'),
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        // Tech
        \Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => env('TELEGRAM_TECH_GROUP_ID'),
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }

    return response()->json([
        'message'=>'heartbeat received'
    ]);
}

public function registerSensor(Request $request)
{
    $request->validate([
        'device_uid'   => 'required|string',
        'device_token' => 'required|string',
        'rom_address'  => 'required|string'
    ]);

    $device = Device::where('device_uid', $request->device_uid)
        ->where('device_token', $request->device_token)
        ->first();

    if (!$device) {

        return response()->json([
            'error' => 'device not found'
        ], 401);
    }

    $sensor = Sensor::where('rom_address', $request->rom_address)->first();

    if (!$sensor) {

        $sensor = Sensor::create([
            'device_id'   => $device->id,
            'rom_address' => $request->rom_address
        ]);
    }

    return response()->json([
        'message' => 'sensor registered',
        'sensor_id' => $sensor->id
    ]);
}

}
