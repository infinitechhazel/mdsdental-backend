<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $addresses = Address::where('user_id', $user->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses,
                'addresses' => $addresses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching addresses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new address for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Check if user has reached the limit of 4 addresses
            $addressCount = Address::where('user_id', $user->id)->count();
            if ($addressCount >= 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only add up to 4 addresses',
                ], 422);
            }

            // Validate input
            $validated = $request->validate([
                'street' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'is_default' => 'boolean',
            ]);

            // If setting as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                Address::where('user_id', $user->id)->update(['is_default' => false]);
            }

            $address = Address::create([
                'user_id' => $user->id,
                ...$validated,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Address created successfully',
                'data' => $address,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an address.
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Check if address belongs to user
            if ($address->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Validate input
            $validated = $request->validate([
                'street' => 'string|max:255',
                'city' => 'string|max:255',
                'state' => 'string|max:255',
                'postal_code' => 'string|max:20',
                'country' => 'string|max:255',
                'is_default' => 'boolean',
            ]);

            // If setting as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                Address::where('user_id', $user->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'data' => $address,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an address.
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Check if address belongs to user
            if ($address->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set an address as default.
     */
    public function setDefault(Request $request, Address $address): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Check if address belongs to user
            if ($address->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Unset all other defaults
            Address::where('user_id', $user->id)->update(['is_default' => false]);

            // Set this address as default
            $address->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Default address updated successfully',
                'data' => $address,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error setting default address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
