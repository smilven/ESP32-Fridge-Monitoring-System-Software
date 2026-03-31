<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Models\Device;
use Illuminate\Support\Facades\Http;

Schedule::call(function () {

    $token = env('TELEGRAM_BOT_TOKEN');
    $crewId = env('TELEGRAM_CHAT_ID');
    $groupId = env('TELEGRAM_GROUP_ID');
    $techGroupId = env('TELEGRAM_TECH_GROUP_ID');

    // 找出刚刚变 offline 的设备（关键！）
    $devices = Device::where('last_seen', '<', now()->subMinutes(3))
        ->where('status', 'online') // ✅ 只处理从 online -> offline
        ->get();

    foreach ($devices as $device) {

        // 1️⃣ 更新状态
        $device->update(['status' => 'offline']);
        // 2️⃣ 发送 Telegram
        $message = "🔌 <b>DEVICE OFFLINE</b>\n"
            . "Device: {$device->serial_no}\n"
            . "Last Seen: {$device->last_seen}";

             // Crew group
        Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $crewId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
        // QA group
        Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $groupId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        // Tech group
        Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $techGroupId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }

})->everyMinute();