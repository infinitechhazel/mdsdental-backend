<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function build()
    {
        return $this
            ->subject(
                'Appointment Received — ' . config('app.name')
            )
            ->view('emails.booking-received');
    }
}
