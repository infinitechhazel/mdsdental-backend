<?php
// app/Models/AboutTech.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutTech extends Model
{
    protected $table = 'about_techs';

    protected $fillable = [
        'icon_name',
        'name',
        'description',
        'sort_order',
    ];
}