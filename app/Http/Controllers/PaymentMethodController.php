<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    /**
     * Get all payment methods
     */
    public function index(Request $request)
    {
        $settings = Setting::first();

        if (! $settings) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found',
            ], 404);
        }

        $query = PaymentMethod::where('settings_id', $settings->id)
            ->orderBy('created_at', 'asc');

        // FILTER BY is_enabled (optional query param)
        if ($request->has('is_enabled')) {
            $query->where('is_enabled', $request->boolean('is_enabled'));
        }

        $methods = $query->get();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Store payment method (admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:50', // cash, gcash, bpi
            'display_name' => 'required|string|max:255',
            'type' => 'required|in:cash,ewallet,bank',
            'account_number' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'qr_code' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // max 2MB
            'is_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = Setting::first();

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

        if ($request->hasFile('qr_code')) {
            $file = $request->file('qr_code');
            $filename = time().'_'.$request->key.'_'.$file->getClientOriginalName();
            $file->move(public_path('images/qr_codes'), $filename);
            $qrPath = 'images/qr_codes/'.$filename;
        }

        $paymentMethod = PaymentMethod::create([
            'settings_id' => $settings->id,
            'key' => $request->key,
            'display_name' => $request->display_name,
            'type' => $request->type,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'qr_code' => $qrPath ?? null,
            'is_enabled' => $request->is_enabled ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment method created successfully',
            'data' => $paymentMethod,
        ]);
    }

    /**
     * Update payment method
     */
    public function update(Request $request, $id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (! $paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|string|max:50',
            'display_name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:cash,ewallet,bank',
            'account_number' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'qr_code' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'key',
            'display_name',
            'type',
            'account_number',
            'account_name',
            'is_enabled',
        ]);

        // HANDLE FILE UPLOAD
        if ($request->hasFile('qr_code')) {
            $file = $request->file('qr_code');
            $filename = time().'_'.$request->key.'_'.$file->getClientOriginalName();
            $file->move(public_path('images/qr_codes'), $filename);

            $data['qr_code'] = 'images/qr_codes/'.$filename;
        }

        $paymentMethod->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully',
            'data' => $paymentMethod,
        ]);
    }

    /**
     * Delete payment method
     */
    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (! $paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        // DELETE QR IMAGE FILE IF EXISTS
        if ($paymentMethod->qr_code) {
            $filePath = public_path($paymentMethod->qr_code);

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully',
        ]);
    }
}
