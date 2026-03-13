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

}



