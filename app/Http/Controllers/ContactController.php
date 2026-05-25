<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactReply;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'subject' => 'nullable|string|max:255',
                'message' => 'required|string|max:5000',
            ]);

            DB::beginTransaction();

            $contact = Contact::create($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thank you for contacting us! We\'ll get back to you within 24 hours.',
                'data' => [
                    'id' => $contact->id,
                    'submitted_at' => $contact->created_at->toISOString(),
                ],
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Please check your input and try again.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Contact form submission error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Simple paginated contacts, newest first
            $contacts = Contact::orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $contacts,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contacts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Contact $contact): JsonResponse
    {
        $contact->load(['replies' => function ($query) {
            $query->orderBy('sent_at', 'asc');
        }]);

        return response()->json([
            'success' => true,
            'data' => $contact,
        ]);
    }

    public function storeReply(Request $request, Contact $contact): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'message' => 'required|string|max:5000',
                'sender_name' => 'required|string|max:255',
                'sender_email' => 'required|email|max:255',
            ]);

            DB::beginTransaction();

            $reply = ContactReply::create([
                'contact_id' => $contact->id,
                'sender_type' => 'admin',
                'sender_name' => $validatedData['sender_name'],
                'sender_email' => $validatedData['sender_email'],
                'message' => $validatedData['message'],
                'sent_at' => now(),
            ]);

            // Update contact
            $contact->update([
                'replied_at' => now(),
                'reply_count' => $contact->replies()->count(),
            ]);

            DB::commit();

            // TODO: Send email notification to customer
            // Mail::to($contact->email)->send(new ContactReplyNotification($reply));

            return response()->json([
                'success' => true,
                'message' => 'Reply sent successfully',
                'data' => $reply,
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reply submission error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply',
            ], 500);
        }
    }

    public function destroy(Contact $contact): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete all replies first (if cascade isn't set up)
            $contact->replies()->delete();

            // Delete the contact
            $contact->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete contact ID: {$contact->id}, Error: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function todayCount(): JsonResponse
    {
        $count = Contact::today()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
                'date' => today()->format('Y-m-d'),
            ],
        ]);
    }
}
