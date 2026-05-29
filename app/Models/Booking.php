<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Service;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'service_id',
        'booking_date',
        'status',
        'notes',
        'name',
        'email',
        'phone',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'service_id' => 'integer',
        'booking_date' => 'datetime',
        'status' => 'string',
        'notes' => 'string',
        'name' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
=======

class Booking extends Model
{
    protected $fillable = [
        'patient_name',
        'patient_email',
        'phone',
        'service',
        'scheduled_at',
        'status',
        'amount',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
}
>>>>>>> 7be0bdfd869870c656e7bc108e0d9a71f501ef93
