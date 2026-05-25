<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportTicketController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'category' => 'required|in:bug_report,feature_request,general_feedback,menu_question,other',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
            'domain' => 'nullable|string|max:255',
            'current_page' => 'nullable|string|max:255',
        ]);

        try {
            $ticket = SupportTicket::create($validated);

            return response()->json([
                'message' => 'Support ticket created successfully',
                'data' => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create support ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        $tickets = SupportTicket::latest()
            ->paginate(15);

        return response()->json([
            'data' => $tickets->items(),
            'pagination' => [
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    public function show(SupportTicket $supportTicket): JsonResponse
    {
        return response()->json([
            'data' => $supportTicket,
        ]);
    }

    public function update(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'resolved_at' => 'sometimes|nullable|date',
        ]);

        $supportTicket->update($validated);

        return response()->json([
            'message' => 'Support ticket updated successfully',
            'data' => $supportTicket,
        ]);
    }

    public function destroy(SupportTicket $supportTicket): JsonResponse
    {
        $supportTicket->delete();

        return response()->json([
            'message' => 'Support ticket deleted successfully',
        ]);
    }
}