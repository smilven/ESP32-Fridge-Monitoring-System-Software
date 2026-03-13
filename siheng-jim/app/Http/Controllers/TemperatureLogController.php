<?php

namespace App\Http\Controllers;

use App\Models\TemperatureLog;

class TemperatureLogController extends Controller
{

    public function download($sensor_id)
    {

        $logs = TemperatureLog::where('sensor_id',$sensor_id)
            ->orderBy('recorded_at','asc')
            ->get();

        return response()->streamDownload(function() use ($logs){

            $handle = fopen('php://output','w');

            fputcsv($handle,['Temperature','Recorded At','Alert Status']);

            foreach($logs as $log){

                fputcsv($handle,[
                    $log->temperature,
                    $log->recorded_at,
                    $log->alert_status == 1 ? 'Alert' : 'Normal'
                ]);

            }

            fclose($handle);

        }, 'temperature_logs.csv');

    }

}