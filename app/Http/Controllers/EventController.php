<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File as FileRule;

class EventController extends Controller
{
    /**
     * Get all events.
     */
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->orderBy('event_name')
            ->get()
            ->map(fn(Event $event) => $this->eventResponse($event));

        return response()->json([
            'events' => $events,
        ]);
    }

    /**
     * Get one event.
     */
    public function show(string $eventId): JsonResponse
    {
        $event = Event::where('event_id', $eventId)
            ->firstOrFail();

        return response()->json([
            'event' => $this->eventResponse($event),
        ]);
    }

    /**
     * Create an event with multiple images.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
                'required',
                FileRule::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('5mb'),
            ],
        ]);

        /*
         * Convert event name to:
         *
         * Dental Awareness 2026
         * ↓
         * dental-awareness-2026
         */
        $eventId = Str::slug(
            $validated['event_name']
        );

        /*
         * Prevent duplicate event IDs.
         */
        if (Event::where('event_id', $eventId)->exists()) {
            return response()->json([
                'message' =>
                'An event with this name already exists.',
            ], 422);
        }

        $directory = public_path(
            "images/events/{$eventId}"
        );

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $images = [];

        foreach ($validated['images'] ?? [] as $file) {
            $filename =
                Str::uuid() . '.' . $file->extension();

            $file->move(
                $directory,
                $filename
            );

            $images[] = [
                'url' =>
                "images/events/{$eventId}/{$filename}",

                'alt' =>
                $validated['event_name'],
            ];
        }

        $event = Event::create([
            'event_id' => $eventId,

            'event_name' =>
            $validated['event_name'],

            'description' =>
            $validated['description'] ?? null,

            'images' => $images,
        ]);

        return response()->json([
            'message' =>
            'Event created successfully.',

            'event' =>
            $this->eventResponse($event),
        ], 201);
    }

    /**
     * Update event information and optionally add images.
     */
    public function update(
        Request $request,
        string $eventId
    ): JsonResponse {
        $event = Event::where('event_id', $eventId)
            ->firstOrFail();

        $validated = $request->validate([
            'event_name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
                'required',
                FileRule::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('5mb'),
            ],
        ]);

        $oldEventId = $event->event_id;

        /*
     * Generate the new event_id from the new event name.
     */
        $newEventId = Str::slug($validated['event_name']);

        /*
     * If the slug changed, make sure another event
     * isn't already using it.
     */
        if (
            $newEventId !== $oldEventId &&
            Event::where('event_id', $newEventId)->exists()
        ) {
            return response()->json([
                'message' => 'An event with this name already exists.',
            ], 422);
        }

        /*
     * Old and new image directories.
     */
        $oldDirectory = public_path(
            "images/events/{$oldEventId}"
        );

        $newDirectory = public_path(
            "images/events/{$newEventId}"
        );

        /*
     * If event_id changed, move the entire image directory.
     */
        if ($oldEventId !== $newEventId) {
            if (is_dir($oldDirectory)) {
                if (!is_dir(dirname($newDirectory))) {
                    mkdir(
                        dirname($newDirectory),
                        0755,
                        true
                    );
                }

                /*
             * Rename/move the directory.
             */
                if (!rename($oldDirectory, $newDirectory)) {
                    return response()->json([
                        'message' => 'Failed to move event image directory.',
                    ], 500);
                }
            } else {
                if (!is_dir($newDirectory)) {
                    mkdir($newDirectory, 0755, true);
                }
            }
        } else {
            if (!is_dir($newDirectory)) {
                mkdir($newDirectory, 0755, true);
            }
        }

        /*
     * Update event information.
     */
        $event->event_id = $newEventId;
        $event->event_name = $validated['event_name'];
        $event->description = $validated['description'] ?? null;

        /*
     * Update existing image URLs if the event_id changed.
     */
        $currentImages = $event->images ?? [];

        if ($oldEventId !== $newEventId) {
            $currentImages = collect($currentImages)
                ->map(function ($image) use ($oldEventId, $newEventId) {
                    $image['url'] = str_replace(
                        "images/events/{$oldEventId}/",
                        "images/events/{$newEventId}/",
                        $image['url']
                    );

                    /*
                 * Also update alt text to the new event name
                 * if desired.
                 */
                    return $image;
                })
                ->values()
                ->all();
        }

        /*
     * Add newly uploaded images.
     */
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename =
                    Str::uuid() . '.' . $file->extension();

                $file->move(
                    $newDirectory,
                    $filename
                );

                $currentImages[] = [
                    'url' =>
                    "images/events/{$newEventId}/{$filename}",

                    'alt' =>
                    $event->event_name,
                ];
            }
        }

        $event->images = $currentImages;

        $event->save();

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $this->eventResponse($event),
        ]);
    }


    /**
     * Delete one image from an event.
     */
    public function deleteImage(
        string $eventId,
        int $imageIndex
    ): JsonResponse {
        $event = Event::where('event_id', $eventId)
            ->firstOrFail();

        $images = $event->images ?? [];

        if (!isset($images[$imageIndex])) {
            return response()->json([
                'message' => 'Image not found.',
            ], 404);
        }

        $image = $images[$imageIndex];

        $filePath = public_path(
            $image['url']
        );

        if (is_file($filePath)) {
            unlink($filePath);
        }

        unset($images[$imageIndex]);

        $event->images = array_values($images);

        $event->save();

        return response()->json([
            'message' =>
            'Event image deleted successfully.',

            'event' =>
            $this->eventResponse($event),
        ]);
    }

    /**
     * Delete entire event.
     */
    public function destroy(
        string $eventId
    ): JsonResponse {
        $event = Event::where('event_id', $eventId)
            ->firstOrFail();

        /*
         * Delete all event images.
         */
        foreach ($event->images ?? [] as $image) {
            $filePath = public_path(
                $image['url']
            );

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        /*
         * Delete event directory.
         */
        $directory = public_path(
            "images/events/{$event->event_id}"
        );

        if (is_dir($directory)) {
            File::deleteDirectory($directory);
        }

        $event->delete();

        return response()->json([
            'message' =>
            'Event deleted successfully.',
        ]);
    }

    /**
     * Format event response.
     */
    private function eventResponse(
        Event $event
    ): array {
        return [
            'id' => $event->id,

            'event_id' =>
            $event->event_id,

            'event_name' =>
            $event->event_name,

            'description' =>
            $event->description,

            'images' =>
            collect($event->images ?? [])
                ->map(fn($image) => [
                    'url' =>
                    asset($image['url']),

                    'alt' =>
                    $image['alt'] ??
                        $event->event_name,
                ])
                ->values()
                ->all(),
        ];
    }
}
