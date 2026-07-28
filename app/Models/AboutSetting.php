<?php
// app/Models/AboutSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutSetting extends Model
{
    protected $table = 'about_settings';

    protected $fillable = [
        'mission',
        'vision',
        'stats',
        'hero_heading',
        'hero_subheading',
        'cta_heading',
        'cta_subheading',
    ];

    protected $casts = [
        'stats' => 'array',
    ];
}