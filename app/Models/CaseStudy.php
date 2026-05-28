<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    protected $fillable = [
        'category',
        'treatment',
        'before_image',
        'after_image',
        'result',
        'duration',
        'rating',
        'testimonial',
        'patient',
    ];
}