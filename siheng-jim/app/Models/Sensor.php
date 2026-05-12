<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $fillable = [
        'device_id',
        'rom_address',
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

    public function temperatureLatest()
    {
        return $this->hasOne(TemperatureLatest::class);
    }

    public function schedules()
    {
        return $this->hasMany(SensorSchedule::class);
    }

    /**
     * Get the effective thresholds for the current time.
     *
     * Checks all active schedules. If the current time falls within a
     * schedule's window, that schedule's thresholds are used.
     * If multiple schedules overlap, the first one (by start_time) wins.
     * Falls back to the sensor's default min/max if no schedule matches.
     *
     * @return array{ min: float|null, max: float|null, name: string }
     */
    public function getCurrentThresholds(): array
    {
        $activeSchedule = $this->schedules()
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->first(fn (SensorSchedule $schedule) => $schedule->isCurrentlyActive());

        if ($activeSchedule) {
            return [
                'min'  => $activeSchedule->min_temp,
                'max'  => $activeSchedule->max_temp,
                'name' => $activeSchedule->name,
            ];
        }

        return [
            'min'  => $this->min_temp,
            'max'  => $this->max_temp,
            'name' => 'Default',
        ];
    }
}