<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $fillable = [
        'device_id',
        'rom_address',
        'name',
        'position',
        'max_temp',
        'min_temp',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function temperatureLogs()
{
    return $this->hasMany(TemperatureLog::class);
}

public function alerts()
{
    return $this->hasMany(Alert::class);
}
}