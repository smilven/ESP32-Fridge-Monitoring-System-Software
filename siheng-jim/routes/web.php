<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('');
});

use App\Http\Controllers\TemperatureLogController;

Route::get('/temperature/download/{sensor_id}', 
    [TemperatureLogController::class,'download']
);



use Illuminate\Support\Facades\Http;

