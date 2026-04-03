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
    $techGroupId = env('TELEGRAM_TECH_GROUP_ID');

    // 找出刚刚变 offline 的设备（关键！）
    $devices = Device::where('last_seen', '<', now()->subMinutes(3))
        ->where('status', 'online') // ✅ 只处理从 online -> offline
        ->get();

    foreach ($devices as $device) {
    $branchName = optional($device->fridge->branch)->name ?? 'Unknown';
        // 1️⃣ 更新状态
        $device->update(['status' => 'offline']);
        // 2️⃣ 发送 Telegram
        $message = "🔌 <b>DEVICE OFFLINE</b>\n"
            . "Device: {$device->serial_no}\n"
            . "Location: {$branchName}\n"
            . "Last Seen: {$device->last_seen}";

        // Tech group
        Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $techGroupId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }

})->everyMinute();