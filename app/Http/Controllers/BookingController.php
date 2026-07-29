<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BookingMailer;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * BookingMailer instance
     */
    public function __construct(
        private BookingMailer $mailer
    ) {}

    /**
     * GET /api/bookings
     * Admin: all bookings with optional filters + pagination.
     */
    public function index(Request $request)
    {
        $query = Booking::with([
            'user:id,name,email',
            'service:id,name'
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('service', function ($service) use ($search) {
                        $service->where('name', 'like', "%{$search}%");
                    });
            });
        }


        $perPage = min(
            (int) $request->get('per_page', 15),
            100
        );


        return response()->json(
            $query
                ->latest('booking_date')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    /**
     * GET /api/my-bookings  (auth:sanctum)
     * Returns only the bookings belonging to the authenticated user.
     */
    public function myBookings(Request $request)
    {
        $bookings = Booking::with([
            'service:id,name'
        ])
            ->where('user_id', $request->user()->id)
            ->latest('booking_date')
            ->get()
            ->map(function ($booking) {

                $dateTime = $booking->booking_date
                    ? Carbon::parse($booking->booking_date)
                    : null;

                return [
                    'id' => $booking->id,

                    'service' => $booking->service
                        ? [
                            'id' => $booking->service->id,
                            'name' => $booking->service->name,
                        ]
                        : null,

                    'booking_date' => $dateTime
                        ? $dateTime->format('Y-m-d H:i:s')
                        : null,

                    'date' => $dateTime
                        ? $dateTime->format('Y-m-d')
                        : null,

                    'time' => $dateTime
                        ? $dateTime->format('H:i:s')
                        : null,

                    'status' => $booking->status,

                    'notes' => $booking->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ]);
    }

    /**
     * GET /api/bookings/{booking}
     */
    public function show($id)
    {
        $booking = Booking::findOrFail($id);
        return response()->json(['success' => true, 'data' => $booking->load(['user', 'service'])]);
    }

    /**
     * POST /api/bookings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'service_id'   => 'required|exists:services,id',
            'booking_date' => 'nullable|date_format:Y-m-d H:i:s',
            'date'         => 'nullable|date',
            'time'         => 'nullable|string',
            'status'       => 'nullable|in:pending,confirmed,cancelled',
            'notes'        => 'nullable|string|max:1000',
            'name'         => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
        ]);


        // Build booking_date from date + time
        if (empty($validated['booking_date'])) {

            if (!$request->filled(['date', 'time'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'booking_date or date + time is required.'
                ], 422);
            }

            try {
                $validated['booking_date'] = Carbon::parse(
                    "{$request->date} {$request->time}"
                )->format('Y-m-d H:i:s');
            } catch (\Exception $e) {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date or time format.'
                ], 422);
            }
        }


        // Assign authenticated user
        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }


        $booking = Booking::create([
            ...$validated,
            'status' => $validated['status'] ?? 'pending',
        ]);


        // Send booking received email
        $this->mailer->received($booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => $booking->load('service'),
        ], 201);
    }

    /**
     * PUT /api/bookings/{booking}
     * Full update (all fields).
     */
    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'service_id'   => 'nullable|exists:services,id',
            'booking_date' => 'nullable|date_format:Y-m-d H:i:s',
            'status'       => 'nullable|in:pending,confirmed,cancelled',
            'notes'        => 'nullable|string|max:1000',
            'name'         => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
        ]);

        $booking->update($validated);
        return response()->json(['message' => 'Booking updated successfully.', 'data' => $booking]);
    }

    /**
     * PATCH /api/bookings/{booking}/status
     * Update booking status and notify customer.
     */
    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::with(['service', 'user'])->findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
            'notes'  => 'nullable|string|max:1000',
        ]);
        $booking->update($validated);

        if ($booking->status === 'confirmed') {
            $this->mailer->confirmed($booking);
        }
        if ($booking->status === 'cancelled') {
            $this->mailer->cancelled($booking);
        }
        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully.',
            'data'    => $booking->fresh(),
        ]);
    }

    /**
     * DELETE /api/bookings/{booking}
     */
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully.']);
    }
}