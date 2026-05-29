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
                $q->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('service', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);

        $bookings = $query->latest('booking_date')->paginate($perPage)->withQueryString();

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users_id',
            'service_id' => 'nullable|exists:services_id',
            'booking_date' => 'nullable|date_format:Y-m-d H:i:s',
            'date' => 'nullable|date',
            'time' => 'nullable|string',
            'status' => 'nullable|in:pending,confirmed,cancelled',
            'package' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if (empty($validated['booking_date'])) {
            if ($request->filled('date') && $request->filled('time')) {
                try {
                    $validated['booking_date'] = Carbon::parse("{$request->date} {$request->time}")->format('Y-m-d H:i:s');
                } catch (\Exception $exception) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date or time format.',
                    ], 422);
                }
            }
        }

        if (empty($validated['booking_date'])) {
            return response()->json([
                'success' => false,
                'message' => 'booking_date or date + time is required.',
            ], 422);
        }

        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        if (empty($validated['notes']) && $request->filled('package')) {
            $validated['notes'] = 'Service: ' . $request->package;
        }

        if ($request->filled('package') && !str_contains($validated['notes'] ?? '', 'Service:')) {
            $validated['notes'] = trim(($validated['notes'] ?? '') . ' Service: ' . $request->package);
        }

        $booking = Booking::create(array_merge($validated, [
            'status' => $validated['status'] ?? 'pending',
        ]));

        try {
            $siteName = config('app.name', 'MDS Dental');
            $adminEmail = env('ADMIN_EMAIL', config('mail.from.address'));
            $fromEmail = config('mail.from.address');
            $customerEmail = $validated['email'] ?? optional($request->user())->email;
            $customerName = $validated['name'] ?? optional($request->user())->name ?? 'Guest';
            $serviceLabel = $request->input('package', 'Appointment');

            if ($customerEmail) {
                $customerSubject = 'Appointment Received — ' . $siteName;
                Mail::raw(
                    "Hello {$customerName},\n\nYour appointment request has been received.\n\n" .
                    "Date: {$booking->booking_date}\n" .
                    "Service: {$serviceLabel}\n" .
                    "Notes: {$booking->notes}\n\n" .
                    "We will contact you shortly to confirm your appointment.",
                    function ($message) use ($customerEmail, $customerSubject, $fromEmail, $siteName) {
                        $message->to($customerEmail)
                            ->subject($customerSubject)
                            ->from($fromEmail, $siteName);
                    }
                );
            }

            if ($adminEmail) {
                $adminSubject = 'New Booking Created — ' . $siteName;
                Mail::raw(
                    "A new booking has been created.\n\n" .
                    "Customer: {$customerName}\n" .
                    "Email: {$customerEmail}\n" .
                    "Date: {$booking->booking_date}\n" .
                    "Service: {$serviceLabel}\n" .
                    "Notes: {$booking->notes}\n", 
                    function ($message) use ($adminEmail, $adminSubject, $fromEmail, $siteName) {
                        $message->to($adminEmail)
                            ->subject($adminSubject)
                            ->from($fromEmail, $siteName);
                    }
                );
            }
        } catch (\Exception $e) {
            Log::error('Booking email notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => $booking,
        ], 201);
    }

    public function show(Booking $booking)
    {
        $booking->load(['user', 'service']);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'service_id' => 'nullable|exists:services,id',
            'booking_date' => 'nullable|date_format:Y-m-d H:i:s',
            'status' => 'nullable|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $booking->update($validated);

        return response()->json([
            'message' => 'Booking updated successfully.',
            'data' => $booking,
        ]);
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully.',
        ]);
    }
}
