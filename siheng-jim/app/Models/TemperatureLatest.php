<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemperatureLatest extends Model
{
    protected $table = 'temperature_latest';

    protected $fillable = [
        'sensor_id',
        'temperature',
        'alert_status',
        'recorded_at'
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
}