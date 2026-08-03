<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);

        $monthlyRevenue = Booking::with('service:id,price')
            ->where('status', 'confirmed')
            ->whereYear('booking_date', $year)
            ->whereMonth('booking_date', $month)
            ->get()
            ->sum(fn($booking) => $booking->service?->price ?? 0);

        return response()->json([
            'todays_bookings' => Booking::whereDate('booking_date', $today)
                ->count(),

            'active_patients' => Booking::whereYear('booking_date', $year)
                ->whereMonth('booking_date', $month)
                ->distinct()
                ->count('email'),

            'monthly_revenue' => $monthlyRevenue,

            'pending_appointments' => Booking::where('status', 'pending')
                ->count(),

            'recent_bookings' => Booking::with('service')
                ->latest('booking_date')
                ->take(5)
                ->get()
                ->map(function ($booking) {
                    return [
                        'name' => $booking->name,
                        'service' => $booking->service?->name,
                        'booking_date' => $booking->booking_date,
                        'booking_time' => $booking->booking_time,
                        'status' => $booking->status,
                    ];
                }),
        ]);
    }
}
