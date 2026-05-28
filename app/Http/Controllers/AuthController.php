<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'] ?? null,
                'password' => Hash::make($validatedData['password']),
                'role' => 'customer',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful!',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                ]
            ], 201);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Please check your input and try again.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            Log::error('Registration error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {

            $validatedData = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($validatedData)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials. Please check your email and password.',
                ], 401);
            }

            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                ]
            ], 200);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Please check your input and try again.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            Log::error('Login error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {

            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'email_verified' => !is_null($user->email_verified_at),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {

            Log::error('Get user error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get user information.',
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully!',
            ], 200);

        } catch (\Exception $e) {

            Log::error('Logout error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {

            $user = $request->user();

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
            ]);

            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'email_verified' => !is_null($user->email_verified_at),
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Please check your input and try again.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            Log::error('Update profile error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
            ], 500);
        }
    }
}