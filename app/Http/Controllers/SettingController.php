<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get settings
     */
    public function show()
    {
        $settings = Setting::first();

        // If no record exists yet, auto-create default settings
        if (! $settings) {
            $settings = Setting::create([
                'restaurant_name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'delivery_fee' => 0,
                'maintenance_mode' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'maintenance_mode' => 'nullable|boolean',
        ]);

        $settings = Setting::first();

        if (! $settings) {
            $settings = Setting::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => $settings,
        ]);
    }
}
