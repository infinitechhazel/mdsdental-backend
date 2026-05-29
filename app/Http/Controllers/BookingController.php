<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::query();

        // If NOT admin → only see own bookings
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        // filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('scheduled_at', [
                $request->date_from,
                $request->date_to
            ]);
        }

        return $query->latest()->paginate(10);
    }

    // show single booking
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::findOrFail($id);

        // non-admin cannot access others bookings
        if ($user->role !== 'admin' && $booking->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return $booking;
    }

    // admin status update
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        // admin only
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $booking = Booking::findOrFail($id);

        $request->validate([
            'status' => 'required|in:Pending,Confirmed,In Progress,Completed,Cancelled'
        ]);

        $booking->update([
            'status' => $request->status
        ]);

        return response()->json($booking);
    }

    // create booking
    public function store(Request $request)
    {
        $data = $request->validate([
            'service' => 'required|string',
            'scheduled_at' => 'required|date',
            'phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // auto attach authenticated user
        $data['user_id'] = $request->user()->id;

        // optional autofill
        $data['patient_name'] = $request->user()->name;
        $data['patient_email'] = $request->user()->email;

        return Booking::create($data);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::findOrFail($id);

        if (
            $user->role !== 'admin' &&
            $booking->user_id !== $user->id
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }
}
