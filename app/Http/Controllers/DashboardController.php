<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        return response()->json([
            "todays_bookings" => Booking::whereDate('scheduled_at', $today)->count(),

            "active_patients" => Booking::distinct('patient_email')->count('patient_email'),

            "monthly_revenue" => Booking::whereMonth('scheduled_at', $today->month)
                ->sum('amount'),

            "pending_followups" => Booking::where('status', 'Pending')->count(),

            "recent_bookings" => Booking::latest()
                ->take(5)
                ->get()
                ->map(fn ($b) => [
                    "patient" => $b->patient_name,
                    "service" => $b->service,
                    "time" => $b->scheduled_at->format('h:i A'),
                    "status" => $b->status,
                    "avatar" => collect(explode(' ', $b->patient_name))
                        ->map(fn ($w) => strtoupper($w[0]))
                        ->take(2)
                        ->implode(''),
                ]),
        ]);
    }
}