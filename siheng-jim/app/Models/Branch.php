<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
        'state',
        'phone_number',
        'type',
        'image_url'
    ];

    public function fridges()
{
    return $this -> hasMany(Fridge::class);
}
    public function sensors()
    {
        return $this->hasManyThrough(
            Sensor::class,
            Device::class,
            'fridge_id',   // Device -> Fridge
            'device_id',   // Sensor -> Device
            'id',          // Branch id
            'id'           // Device id
        )->join('fridges', 'devices.fridge_id', '=', 'fridges.id')
        ->whereColumn('fridges.branch_id', 'branches.id');
    }
}



