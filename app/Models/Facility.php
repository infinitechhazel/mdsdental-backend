<?php

// app/Models/Facility.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $fillable = [
        'icon_name',
        'label',
        'name',
        'description',
        'bullets',
        'accent',
        'image_path',
        'sort_order',
    ];

    protected $casts = [
        'bullets' => 'array',
    ];

    /**
     * Return the public URL for the image, or null if none stored.
     * Images live in /public/images/facilities/ — served directly by the web server.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        // image_path is stored as a relative web path, e.g. "images/facilities/abc123.jpg"
        return '/' . ltrim($this->image_path, '/');
    }

    protected $appends = ['image_url'];
}