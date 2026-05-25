<?php
/**
 * Updated Event Model with Status Support
 * 
 * Replace your app/Models/Event.php with this content
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'user_id',
        'event_type',
        'guests',
        'preferred_date',
        'preferred_time',
        'venue_area',
        'status', // Added status field
        'confirmed_at', // Added confirmed_at field
        'completed_at', // Added completed_at field
        'admin_notes', // Added admin_notes field
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preferred_date' => 'date',
        'preferred_time' => 'string',
        'guests' => 'integer',
        'user_id' => 'integer',
        'confirmed_at' => 'datetime', // Added datetime cast
        'completed_at' => 'datetime', // Added datetime cast
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['formatted_date', 'formatted_time', 'status_display'];

    /**
     * Get the user that owns the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->preferred_date->format('F d, Y');
    }

    /**
     * Get the formatted time.
     */
    public function getFormattedTimeAttribute(): string
    {
        return date('g:i A', strtotime($this->preferred_time));
    }

    /**
     * Get the display name for event type.
     */
    public function getEventTypeDisplayAttribute(): string
    {
        $types = [
            'wedding' => 'Wedding',
            'corporate' => 'Corporate Event',
            'birthday' => 'Birthday Party',
            'conference' => 'Conference',
            'other' => 'Other',
        ];

        return $types[$this->event_type] ?? ucfirst($this->event_type);
    }

    /**
     * Get the display name for venue area.
     */
    public function getVenueAreaDisplayAttribute(): string
    {
        $areas = [
            'vip_area' => 'VIP Area',
            'main_hall' => 'Main Hall',
            'private_room' => 'Private Room',
        ];

        return $areas[$this->venue_area] ?? ucfirst(str_replace('_', ' ', $this->venue_area));
    }

    /**
     * Get the display name for status
     */
    public function getStatusDisplayAttribute(): string
    {
        $statuses = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }
}
