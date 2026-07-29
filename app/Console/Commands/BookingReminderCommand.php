<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Mail\BookingReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingReminderCommand extends Command
{
    protected $signature = 'bookings:reminder';

    protected $description = 'Send reminder emails one day before appointments';

    public function handle()
    {
        $target = now()->addDay();
        $bookings = Booking::where('status', 'confirmed')
            ->whereNull('reminder_sent_at')
            ->whereBetween('booking_date', [
                $target->copy()->startOfMinute(),
                $target->copy()->endOfMinute(),
            ])
            ->get();
        foreach ($bookings as $booking) {

            Mail::to($booking->email)
                ->send(new BookingReminder($booking));

            $booking->reminder_sent_at = now();
            $booking->save();
        }
        return Command::SUCCESS;
    }
}
