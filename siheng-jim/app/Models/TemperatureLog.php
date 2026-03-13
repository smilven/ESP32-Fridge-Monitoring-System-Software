<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemperatureLog extends Model
{
    protected $fillable = [
        'sensor_id',
        'temperature',
        'alert_status',
        'recorded_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'alert_status' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
    public function alerts()
{
    return $this->hasMany(Alert::class);
}
}