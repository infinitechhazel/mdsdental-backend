<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewBookingAdmin extends Mailable
{
    use Queueable, SerializesModels;


    public function __construct(
        public Booking $booking
    ) {}


    public function build()
    {
        return $this
            ->subject(
                'New Appointment Booking — ' . config('app.name')
            )
            ->view('emails.booking-new-admin');
    }
}