<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'replied_at',
        'reply_count',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function replies(): HasMany
    {
        return $this->hasMany(ContactReply::class)->orderBy('sent_at', 'asc');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeReplied($query)
    {
        return $query->whereNotNull('replied_at');
    }

    public function scopeUnreplied($query)
    {
        return $query->whereNull('replied_at');
    }
}