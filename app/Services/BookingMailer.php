<?php

namespace App\Services;

use App\Models\Booking;
use App\Mail\BookingReceived;
use App\Mail\NewBookingAdmin;
use App\Mail\BookingConfirmed;
use App\Mail\BookingCancelled;
use App\Mail\BookingReminder;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BookingMailer
{

    public function received(Booking $booking): void
    {
        try {

            $customerEmail = $booking->email
                ?? optional($booking->user)->email;

            if ($customerEmail) {
                Mail::to($customerEmail)
                    ->send(new BookingReceived($booking));
            }


            $adminEmail = config('mail.admin_email');

            if ($adminEmail) {
                Mail::to($adminEmail)
                    ->send(new NewBookingAdmin($booking));
            }
        } catch (\Exception $e) {

            Log::error(
                'Booking received email failed: ' . $e->getMessage()
            );
        }
    }

    public function confirmed(Booking $booking): void
    {
        $this->send(
            $booking,
            new BookingConfirmed($booking)
        );
    }

    public function cancelled(Booking $booking): void
    {
        $this->send(
            $booking,
            new BookingCancelled($booking)
        );
    }

    public function reminder(Booking $booking): void
    {
        $this->send(
            $booking,
            new BookingReminder($booking)
        );
    }

    private function send(Booking $booking, $mail): void
    {
        try {

            $email = $booking->email
                ?? optional($booking->user)->email;
            if ($email) {
                Mail::to($email)
                    ->send($mail);
            }
        } catch (\Exception $e) {
            Log::error(
                'Booking email failed: ' . $e->getMessage()
            );
        }
    }
}
