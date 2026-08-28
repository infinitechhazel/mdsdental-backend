<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'event_id',
        'event_name',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];
}
