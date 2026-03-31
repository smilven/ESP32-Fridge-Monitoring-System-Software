<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Alert;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        // 只处理按钮点击
        if (!isset($data['callback_query'])) {
            return response()->json(['ok' => true]);
        }

        $callback = $data['callback_query'];
        $callbackData = $callback['data'];

        if (str_starts_with($callbackData, 'report_')) {

            $alertId = str_replace('report_', '', $callbackData);

            $token = env('TELEGRAM_BOT_TOKEN');
            $techGroup = env('TELEGRAM_TECH_GROUP_ID');
            $qaGroup = env('TELEGRAM_GROUP_ID');

            DB::transaction(function () use ($alertId, $callback, $token, $techGroup, $qaGroup) {

                // 🔒 锁住 row（防重复点击）
                $alert = Alert::where('id', $alertId)
                    ->where('reported', false)
                    ->lockForUpdate()
                    ->first();

                // ❌ 已经被 report
                if (!$alert) {
                    Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                        'callback_query_id' => $callback['id'],
                        'text' => 'Already reported ✅',
                    ]);
                    return;
                }

                // ✅ 标记为已 report
                $alert->update([
                    'reported' => true
                ]);

                // 📄 message
                $message = "🛠 ISSUE REPORTED
Branch: {$alert->sensor->device->fridge->branch->name}
Sensor: {$alert->sensor->rom_address}
Alert ID: {$alert->id}";

                // 🔧 technical group
                Http::timeout(3)->retry(2, 100)->get(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    [
                        'chat_id' => $techGroup,
                        'text' => $message
                    ]
                );

                // 🧪 QA group
                Http::timeout(3)->retry(2, 100)->get(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    [
                        'chat_id' => $qaGroup,
                        'text' => $message
                    ]
                );

                // 👤 回复用户（停止 loading）
                Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                    'callback_query_id' => $callback['id'],
                    'text' => 'Reported to technical ✅',
                ]);
            });
        }

        return response()->json(['ok' => true]);
    }
}