<?php
/**
 * Updated Event Controller with Status Management
 * 
 * Replace your app/Http/Controllers/EventController.php with this content
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::with('user');

        // Filter by user_id if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by email if provided
        if ($request->has('email')) {
            $query->where('email', $request->email);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $events = $query->orderBy('preferred_date', 'desc')
            ->orderBy('preferred_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ], 200);
    }

    /**
     * Store a newly created event booking.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'userId' => 'nullable|integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'eventType' => 'required|string|in:wedding,corporate,birthday,conference,other',
            'guests' => 'required|integer|min:1',
            'preferredDate' => 'required|date|after_or_equal:today',
            'preferredTime' => 'required|date_format:H:i',
            'venueArea' => 'required|string|in:vip_area,main_hall,private_room,garden_area',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle both camelCase (userId) and snake_case (user_id)
        $userId = $request->input('userId') ?? $request->input('user_id');

        $event = Event::create([
            'name' => $request->name,
            'email' => $request->email,
            'user_id' => $userId,
            'event_type' => $request->eventType,
            'guests' => $request->guests,
            'preferred_date' => $request->preferredDate,
            'preferred_time' => $request->preferredTime,
            'venue_area' => $request->venueArea,
            'status' => 'pending', // Set default status to pending
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event booked successfully',
            'data' => $event
        ], 201);
    }

    /**
     * Display the specified event.
     */
    public function show(string $id): JsonResponse
    {
        $event = Event::with('user')->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $event
        ], 200);
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'userId' => 'nullable|integer|exists:users,id',
            'eventType' => 'sometimes|string|in:wedding,corporate,birthday,conference,other',
            'guests' => 'sometimes|integer|min:1',
            'preferredDate' => 'sometimes|date|after_or_equal:today',
            'preferredTime' => 'sometimes|date_format:H:i',
            'venueArea' => 'sometimes|string|in:vip_area,main_hall,private_room,garden_area',
            'status' => 'sometimes|string|in:pending,confirmed,completed,cancelled', // Added status validation
            'adminNotes' => 'nullable|string', // Added admin notes
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [
            'name' => $request->name ?? $event->name,
            'email' => $request->email ?? $event->email,
            'user_id' => $request->userId ?? $event->user_id,
            'event_type' => $request->eventType ?? $event->event_type,
            'guests' => $request->guests ?? $event->guests,
            'preferred_date' => $request->preferredDate ?? $event->preferred_date,
            'preferred_time' => $request->preferredTime ?? $event->preferred_time,
            'venue_area' => $request->venueArea ?? $event->venue_area,
            'admin_notes' => $request->adminNotes ?? $event->admin_notes,
        ];

        if ($request->has('status')) {
            $newStatus = $request->status;
            $updateData['status'] = $newStatus;

            if ($newStatus === 'confirmed' && !$event->confirmed_at) {
                $updateData['confirmed_at'] = now();
            }

            if ($newStatus === 'completed' && !$event->completed_at) {
                $updateData['completed_at'] = now();
            }
        }

        $event->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event
        ], 200);
    }

    /**
     * Remove the specified event.
     */
    public function destroy(string $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ], 200);
    }
}
