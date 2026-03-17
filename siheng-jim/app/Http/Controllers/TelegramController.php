<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Alert;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        if (!isset($data['callback_query'])) {
            return response()->json(['ok' => true]);
        }

        $callback = $data['callback_query'];
        $callbackData = $callback['data'];

        if (str_starts_with($callbackData, 'report_')) {

            $alertId = str_replace('report_', '', $callbackData);
            $alert = Alert::find($alertId);

            if (!$alert) {
                return response()->json(['ok' => true]);
            }

            $token = env('TELEGRAM_BOT_TOKEN');
            $techGroup = env('TELEGRAM_TECH_GROUP_ID'); // 新的
            $qaGroup = env('TELEGRAM_GROUP_ID'); // 原本的

            // 🚫 防 spam
            if ($alert->reported) {

                Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                    'callback_query_id' => $callback['id'],
                    'text' => 'Already reported ✅',
                ]);

                return response()->json(['ok' => true]);
            }

            // ✅ 标记
            $alert->update([
                'reported' => true
            ]);

            $message = "🛠 ISSUE REPORTED
Branch: {$alert->sensor->device->fridge->branch->name}
Sensor: {$alert->sensor->rom_address}
Alert ID: {$alert->id}";

            // 🔧 technical group
            Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $techGroup,
                'text' => $message
            ]);

            // 🧪 QA group
            Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $qaGroup,
                'text' => $message
            ]);

            // 👤 用户提示
            Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                'callback_query_id' => $callback['id'],
                'text' => 'Reported to technical ✅',
            ]);
        }

        return response()->json(['ok' => true]);
    }
}