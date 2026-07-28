<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    /**
     * GET /api/bookings
     * Admin: all bookings with optional filters + pagination.
     */
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'service']);

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
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('service', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage  = (int) $request->get('per_page', 15);
        $bookings = $query->latest('booking_date')->paginate($perPage)->withQueryString();

        return response()->json($bookings);
    }

    /**
     * GET /api/my-bookings  (auth:sanctum)
     * Returns only the bookings belonging to the authenticated user.
     */
    public function myBookings(Request $request)
    {
        $bookings = Booking::with(['service'])
            ->where('user_id', $request->user()->id)
            ->latest('booking_date')
            ->get()
            ->map(function ($booking) {
                $dt = $booking->booking_date
                    ? Carbon::parse($booking->booking_date)
                    : null;

                return [
                    'id'              => $booking->id,
                    'package'         => $booking->notes
                                            ? preg_replace('/^Service:\s*/i', '', $booking->notes)
                                            : optional($booking->service)->name ?? '—',
                    'date'            => $dt ? $dt->format('Y-m-d') : null,
                    'time'            => $dt ? $dt->format('H:i:s') : null,
                    'payment_status'  => $booking->status ?? 'pending',
                    'reservation_fee' => $booking->reservation_fee ?? 0,
                ];
            });

        return response()->json([
            'success'  => true,
            'bookings' => $bookings,
        ]);
    }

    /**
     * POST /api/bookings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
          'service_id' => 'nullable|exists:services,id',
            'booking_date' => 'nullable|date_format:Y-m-d H:i:s',
            'date'         => 'nullable|date',
            'time'         => 'nullable|string',
            'status'       => 'nullable|in:pending,confirmed,cancelled',
            'package'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
            'name'         => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
        ]);

        // Build booking_date from date + time if not supplied directly
        if (empty($validated['booking_date'])) {
            if ($request->filled('date') && $request->filled('time')) {
                try {
                    $validated['booking_date'] = Carbon::parse("{$request->date} {$request->time}")->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Invalid date or time format.'], 422);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'booking_date or date + time is required.'], 422);
            }
        }

        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        // Store service label in notes
        if ($request->filled('package')) {
            $validated['notes'] = 'Service: ' . $request->package;
        }

        $booking = Booking::create(array_merge($validated, [
            'status' => $validated['status'] ?? 'pending',
        ]));

        // Email notifications (non-fatal)
        try {
            $siteName      = config('app.name', 'MDS Dental');
            $adminEmail    = env('ADMIN_EMAIL', config('mail.from.address'));
            $fromEmail     = config('mail.from.address');
            $customerEmail = $validated['email'] ?? optional($request->user())->email;
            $customerName  = $validated['name']  ?? optional($request->user())->name ?? 'Guest';
            $serviceLabel  = $request->input('package', 'Appointment');

            if ($customerEmail) {
                Mail::raw(
                    "Hello {$customerName},\n\nYour appointment request has been received.\n\n" .
                    "Date: {$booking->booking_date}\nService: {$serviceLabel}\n\n" .
                    "We will contact you shortly to confirm.",
                    fn ($m) => $m->to($customerEmail)->subject("Appointment Received — {$siteName}")->from($fromEmail, $siteName)
                );
            }

            if ($adminEmail) {
                Mail::raw(
                    "New booking:\n\nCustomer: {$customerName}\nEmail: {$customerEmail}\n" .
                    "Date: {$booking->booking_date}\nService: {$serviceLabel}",
                    fn ($m) => $m->to($adminEmail)->subject("New Booking — {$siteName}")->from($fromEmail, $siteName)
                );
            }
        } catch (\Exception $e) {
            Log::error('Booking email notification failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Booking created successfully.', 'data' => $booking], 201);
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
     * PATCH /api/bookings/{id}/status
     * Admin approve / reject action.
     * FIX: This matches the route definition in api.php.
     */
    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $booking->update($validated);

        // Notify customer of the status change (non-fatal)
        try {
            $siteName      = config('app.name', 'MDS Dental');
            $fromEmail     = config('mail.from.address');
            $customerEmail = $booking->email ?? optional($booking->user)->email;
            $customerName  = $booking->name  ?? optional($booking->user)->name ?? 'Patient';

            if ($customerEmail) {
                $statusLabel = $validated['status'] === 'confirmed' ? 'Confirmed ✓' : 'Cancelled ✗';
                $body        = "Hello {$customerName},\n\n" .
                               "Your appointment has been {$statusLabel}.\n\n" .
                               ($validated['notes'] ? "Message from clinic:\n{$validated['notes']}\n\n" : '') .
                               "Date: {$booking->booking_date}";

                Mail::raw($body, fn ($m) =>
                    $m->to($customerEmail)
                      ->subject("Appointment {$statusLabel} — {$siteName}")
                      ->from($fromEmail, $siteName)
                );
            }
        } catch (\Exception $e) {
            Log::error('Status update email failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Booking status updated.', 'data' => $booking]);
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