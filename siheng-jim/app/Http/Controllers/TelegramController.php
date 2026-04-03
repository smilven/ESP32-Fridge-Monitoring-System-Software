<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;

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
        $messageId = $callback['message']['message_id'];
        $chatId = $callback['message']['chat']['id'];

        if (str_starts_with($callbackData, 'report_')) {
            $alertId = str_replace('report_', '', $callbackData);
            $token = env('TELEGRAM_BOT_TOKEN');
            $techGroup = env('TELEGRAM_TECH_GROUP_ID');
            $qaGroup = env('TELEGRAM_GROUP_ID');

            try {
                DB::transaction(function () use ($alertId, $callback, $token, $techGroup, $qaGroup, $messageId, $chatId) {
                    
                    // 1. 🔒 锁住 row 并且检查是否已经 report
                    $alert = Alert::where('id', $alertId)
                        ->lockForUpdate()
                        ->first();

                    // 2. 如果警报不存在，或者已经 report 过了
                    if (!$alert || $alert->reported) {
                        // 移除原消息的按钮，防止以后再点
                        $this->removeButton($token, $chatId, $messageId);
                        
                        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                            'callback_query_id' => $callback['id'],
                            'text' => 'Already reported by someone else ✅',
                        ]);
                        return;
                    }

                    // 3. ✅ 立即标记为已 report (在发消息之前，防止发送过程中重入)
                    $alert->update(['reported' => true]);

                    // 4. 🛠 移除 Telegram 界面上的按钮 (关键：让用户点不了第二次)
                    $this->removeButton($token, $chatId, $messageId);

                    // 5. 📄 准备消息内容
                    $branchName = $alert->sensor->device->fridge->branch->name ?? 'Unknown';
                    $rom = $alert->sensor->rom_address;
                    $message = "🛠 ISSUE REPORTED\nBranch: {$branchName}\nSensor: {$rom}\nAlert ID: {$alertId}";

                    // 6. 发送给相关群组
                    Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $techGroup,
                        'text' => $message
                    ]);

                    Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $qaGroup,
                        'text' => $message
                    ]);

                    // 7. 停止用户手机上的 loading 状态
                    Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                        'callback_query_id' => $callback['id'],
                        'text' => 'Reported to Technical ✅',
                    ]);
                });
            } catch (\Exception $e) {
                Log::error("Report Error: " . $e->getMessage());
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * 移除消息底部的按钮
     */
    private function removeButton($token, $chatId, $messageId)
    {
        Http::post("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => json_encode(['inline_keyboard' => []]) // 发送空的键盘即为删除
        ]);
    }
}