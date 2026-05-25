<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'author',
        'video_url',
        'thumbnail',
        'image',
        'draft',
    ];
}
