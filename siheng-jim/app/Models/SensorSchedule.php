<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorSchedule extends Model
{
    protected $fillable = [
        'sensor_id',
        'name',
        'start_time',
        'end_time',
        'min_temp',
        'max_temp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_temp'  => 'float',
        'max_temp'  => 'float',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Check whether this schedule is currently active (time-based).
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now   = now()->format('H:i:s');
        $start = $this->start_time;
        $end   = $this->end_time;

        // Handle overnight schedules (e.g. 22:00 - 06:00)
        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        } else {
            return $now >= $start || $now <= $end;
        }
    }
}