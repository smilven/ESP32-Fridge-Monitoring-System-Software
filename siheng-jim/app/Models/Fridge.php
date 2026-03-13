<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fridge extends Model
{
    protected $fillable = [
        'branch_id',
        'type',
        'model_number',
        'image_url'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function device()
{
    return $this->hasOne(Device::class);
}
}
