<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'area',
        'phone',
        'email',
        'address',
        'hours',
        'map_query',
        'directions_url',
        'blurb',
        'facebook',
        'instagram',
    ];

    /**
     * A branch has many images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(
            BranchImage::class,
            'branch_id',
            'id'
        )->orderBy('sort_order');
    }
}
