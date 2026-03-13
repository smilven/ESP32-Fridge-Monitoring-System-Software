<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Models\Device;

Schedule::call(function () {

    Device::where('last_seen', '<', now()->subMinutes(1))
        ->update(['status' => 'offline']);

})->everyMinute();