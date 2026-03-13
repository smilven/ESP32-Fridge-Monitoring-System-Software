<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'sensor_id',
        'temperature_log_id',
        'alert_type',
        'message',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function temperatureLog()
    {
        return $this->belongsTo(TemperatureLog::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
