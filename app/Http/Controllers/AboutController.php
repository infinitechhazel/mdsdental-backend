<?php

namespace App\Http\Controllers;

use App\Models\AboutDoctor;
use App\Models\AboutSetting;
use App\Models\AboutTech;
use App\Models\AboutTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    /* ─────────────────────────────────────────
       GET /api/about
    ───────────────────────────────────────── */
    public function show(): JsonResponse
    {
        $settings = AboutSetting::first();
        $doctor   = AboutDoctor::first();
        $timeline = AboutTimeline::orderBy('sort_order')->orderBy('year')->get();
        $tech     = AboutTech::orderBy('sort_order')->get();

        return response()->json([
            'hero_heading'    => $settings?->hero_heading,
            'hero_subheading' => $settings?->hero_subheading,
            'mission'         => $settings?->mission,
            'vision'          => $settings?->vision,
            'cta_heading'     => $settings?->cta_heading,
            'cta_subheading'  => $settings?->cta_subheading,
            'stats'           => $settings?->stats ?? [],

            'doctor' => $doctor ? [
                'name'        => $doctor->name,
                'title'       => $doctor->title,
                'role'        => $doctor->role,
                'bio'         => $doctor->bio,
                'quote'       => $doctor->quote,
                'location'    => $doctor->location,
                'since_year'  => $doctor->since_year,
                'image_url'   => $doctor->image_path
                                    ? asset('storage/' . $doctor->image_path)
                                    : null,
                'credentials' => $doctor->credentials ?? [],
            ] : null,

            'timeline' => $timeline,
            'tech'     => $tech,
        ]);
    }

    /* ─────────────────────────────────────────
       POST /api/about
    ───────────────────────────────────────── */
    public function store(Request $request): JsonResponse
    {
        // ── Settings ──────────────────────────────
        $settingsData = array_filter([
            'mission'         => $request->input('mission'),
            'vision'          => $request->input('vision'),
            'hero_heading'    => $request->input('hero_heading'),
            'hero_subheading' => $request->input('hero_subheading'),
            'cta_heading'     => $request->input('cta_heading'),
            'cta_subheading'  => $request->input('cta_subheading'),
        ], fn($v) => $v !== null);

        if ($request->has('stats')) {
            $settingsData['stats'] = json_decode($request->input('stats'), true) ?? [];
        }

        $settings = AboutSetting::first();
        if ($settings) {
            $settings->update($settingsData);
        } else {
            AboutSetting::create($settingsData);
        }

        // ── Doctor ────────────────────────────────
        $doctorData = array_filter([
            'name'       => $request->input('doctor_name'),
            'title'      => $request->input('doctor_title'),
            'role'       => $request->input('doctor_role'),
            'bio'        => $request->input('doctor_bio'),
            'quote'      => $request->input('doctor_quote'),
            'location'   => $request->input('doctor_location'),
            'since_year' => $request->input('doctor_since_year'),
        ], fn($v) => $v !== null);

        if ($request->has('credentials')) {
            $doctorData['credentials'] = json_decode($request->input('credentials'), true) ?? [];
        }

        // ── Image Upload ──────────────────────────
        if ($request->hasFile('doctor_image')) {
            $file     = $request->file('doctor_image');
            $filename = 'doctor_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/doctors'), $filename);

            $existing = AboutDoctor::first();
            if ($existing?->image_path) {
                $oldPath = public_path('storage/' . $existing->image_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $doctorData['image_path'] = 'doctors/' . $filename;
        }

        $doctor = AboutDoctor::first();
        if ($doctor) {
            $doctor->update($doctorData);
        } else {
            AboutDoctor::create($doctorData);
        }

        return response()->json(['message' => 'Saved successfully.']);
    }

    /* ─────────────────────────────────────────
       TIMELINE
    ───────────────────────────────────────── */
    public function storeTimeline(Request $request): JsonResponse
    {
        $item = AboutTimeline::create([
            'year'        => $request->input('year'),
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'sort_order'  => $request->input('sort_order', 0),
        ]);

        return response()->json($item, 201);
    }

    public function updateTimeline(Request $request, int $id): JsonResponse
    {
        $item = AboutTimeline::findOrFail($id);

        $item->update([
            'year'        => $request->input('year', $item->year),
            'title'       => $request->input('title', $item->title),
            'description' => $request->input('description', $item->description),
            'sort_order'  => $request->input('sort_order', $item->sort_order),
        ]);

        return response()->json($item);
    }

    public function destroyTimeline(int $id): JsonResponse
    {
        AboutTimeline::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    /* ─────────────────────────────────────────
       TECH
    ───────────────────────────────────────── */
    public function storeTech(Request $request): JsonResponse
    {
        $item = AboutTech::create([
            'icon_name'   => $request->input('icon_name', 'Stethoscope'),
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
            'sort_order'  => $request->input('sort_order', 0),
        ]);

        return response()->json($item, 201);
    }

    public function updateTech(Request $request, int $id): JsonResponse
    {
        $item = AboutTech::findOrFail($id);

        $item->update([
            'icon_name'   => $request->input('icon_name', $item->icon_name),
            'name'        => $request->input('name', $item->name),
            'description' => $request->input('description', $item->description),
            'sort_order'  => $request->input('sort_order', $item->sort_order),
        ]);

        return response()->json($item);
    }

    public function destroyTech(int $id): JsonResponse
    {
        AboutTech::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}