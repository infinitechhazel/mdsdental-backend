<?php
// app/Models/AboutDoctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutDoctor extends Model
{
    protected $table = 'about_doctors';

    protected $fillable = [
        'name',
        'title',
        'role',
        'bio',
        'quote',
        'location',
        'since_year',
        'image_path',
        'credentials',
    ];

    protected $casts = [
        'credentials' => 'array',
    ];
}