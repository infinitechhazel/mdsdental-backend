<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'restaurant_name',
        'email',
        'phone',
        'address',
        'delivery_fee',
        'maintenance_mode',
    ];

    protected $casts = [
        'delivery_fee' => 'float',
        'maintenance_mode' => 'boolean',
    ];
}
