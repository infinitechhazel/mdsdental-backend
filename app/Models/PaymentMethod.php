<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'settings_id',
        'key',
        'display_name',
        'type',
        'account_number',
        'account_name',
        'qr_code',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function settings()
    {
        return $this->belongsTo(Setting::class);
    }
}
