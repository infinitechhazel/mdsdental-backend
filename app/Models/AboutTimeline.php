<?php
// app/Models/AboutTimeline.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutTimeline extends Model
{
    protected $table = 'about_timelines';

    protected $fillable = [
        'year',
        'title',
        'description',
        'sort_order',
    ];
}