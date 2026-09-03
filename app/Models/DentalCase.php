<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalCase extends Model
{
    protected $table = 'dental_cases';

    protected $fillable = [
        'title',
        'category',
        'description',
        'before',
        'after',
        'note',
    ];
}
