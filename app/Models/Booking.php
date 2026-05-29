<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'patient_name',
        'patient_email',
        'phone',
        'service',
        'scheduled_at',
        'status',
        'amount',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
}