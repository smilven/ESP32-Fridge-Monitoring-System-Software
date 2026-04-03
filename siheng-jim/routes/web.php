<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\TemperatureLogController;

Route::get('/temperature/download/{sensor_id}', 
    [TemperatureLogController::class,'download']
);



use Illuminate\Support\Facades\Http;

Route::get('/test-tg', function () {
    // 1. 获取 Token（确保 .env 里的变量名正确）
    $token = env('TELEGRAM_BOT_TOKEN'); 
    
    // 2. 这里的 Chat ID 建议先写死你自己的，测试通了再换变量
    // 如果不知道自己的 ID，可以先看下面的“获取 Chat ID”小技巧
    $chat_id = -5202345810;

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    try {
        $response = Http::post($url, [
            'chat_id' => $chat_id,
            'text'    => "🚀 冰箱监控系统测试消息\n状态: 运行正常\n时间: " . now(),
        ]);

        // 直接在浏览器打印返回的 JSON 内容
        return $response->json();

    } catch (\Exception $e) {
        return response()->json([
            'error' => '请求异常',
            'message' => $e->getMessage()
        ]);
    }
});