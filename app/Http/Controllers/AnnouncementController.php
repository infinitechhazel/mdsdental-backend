<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    // Define allowed types for validation
    private array $allowedTypes = ['news', 'promo', 'event'];

    /**
     * Display a listing of announcements.
     */
    public function index()
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();
        return response()->json($announcements);
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:' . implode(',', $this->allowedTypes),
            'badge' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'badge' => $validated['badge'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return response()->json($announcement, 201);
    }

    /**
     * Display the specified announcement.
     */
    public function show(Announcement $announcement)
    {
        return response()->json($announcement);
    }

    /**
     * Update the specified announcement in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:' . implode(',', $this->allowedTypes),
            'badge' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $announcement = Announcement::findOrFail($id);
        $announcement->update($validated);

        return response()->json($announcement);
    }

    /**
     * Remove the specified announcement from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return response()->json(['message' => 'Announcement deleted successfully'], 200);
    }

    /**
     * Get only active announcements (public endpoint).
     * If you want to filter by type or add more rules, you can extend this method.
     */
    public function getActive()
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();
        return response()->json($announcements);
    }
}