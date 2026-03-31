<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;

Route::post('/device/register', [DeviceController::class, 'register']);
Route::post('/device/config', [DeviceController::class,'config']);
Route::post('/device/heartbeat', [DeviceController::class,'heartbeat']);
Route::post('/device/register-sensor', [DeviceController::class, 'registerSensor']);
Route::post('/device/status', [DeviceController::class, 'updateStatus']);

use App\Http\Controllers\TelegramController;
Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
