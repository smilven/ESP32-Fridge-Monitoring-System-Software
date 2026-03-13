<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
         'device_uid',
        'device_token',
        'fridge_id',
        'fridge_id',
        'serial_no',
        'status',
        'firmware_version',
        'wifi_ssid',
        'wifi_password',
        'mqtt_broker',
        'mqtt_port',
        'mqtt_topic',
        'mqtt_username',
        'mqtt_password',
        'last_seen',
    ];

    public function fridge()
    {
        return $this->belongsTo(Fridge::class);
    }

    public function sensors()
{
    return $this->hasMany(Sensor::class);
}
}