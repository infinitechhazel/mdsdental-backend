<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number',
        'name',
        'email',
        'category',
        'subject',
        'message',
        'domain',
        'current_page',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate a unique ticket number
     * Format: INF-YYYY-#### (e.g., INF-2025-0001)
     */
    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $prefix = "INF-{$year}-";
        
        // Get the latest ticket for this year
        $latestTicket = self::where('ticket_number', 'LIKE', "{$prefix}%")
            ->orderBy('ticket_number', 'desc')
            ->first();
        
        if ($latestTicket) {
            // Extract the number part and increment
            $lastNumber = (int) substr($latestTicket->ticket_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // First ticket of the year
            $newNumber = 1;
        }
        
        // Format: INF-2025-0001
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to auto-generate ticket number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }
}