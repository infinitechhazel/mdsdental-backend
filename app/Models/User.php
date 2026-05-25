<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'zip_code',
        'password',
        'role', // Added role to fillable fields
        'status', // Add this line
        'verification_token',
        'verification_token_expiry',
        'email_verified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
         'verification_token',
    ];

   
     protected $casts = [
        'email_verified_at' => 'datetime',
        'verification_token_expiry' => 'datetime',
        'email_verified' => 'boolean',
        'password' => 'hashed',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
