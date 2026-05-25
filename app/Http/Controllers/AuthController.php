<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:10',
                'password' => 'required|string|min:8|confirmed',
                'verification_token' => 'required|string',
                'verification_token_expiry' => 'required|date',
                'email_verified' => 'boolean',
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'zip_code' => $validatedData['zip_code'] ?? null,
                'password' => Hash::make($validatedData['password']),
                'role' => 'customer',
                'verification_token' => $validatedData['verification_token'],
                'verification_token_expiry' => $validatedData['verification_token_expiry'],
                'email_verified' => false,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'city' => $user->city,
                        'zip_code' => $user->zip_code,
                        'role' => $user->role,
                        'email_verified' => $user->email_verified,
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
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return response()->json(['message' => 'Token is required'], 400);
            }

            $user = User::where('verification_token', $token)->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid or expired token'], 400);
            }

            if ($user->verification_token_expiry && Carbon::now()->isAfter($user->verification_token_expiry)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], 400);
            }

            $user->email_verified = true;
            $user->verification_token = null;
            $user->verification_token_expiry = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Email verification error:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Server error during verification'
            ], 500);
        }
    }


    /**
     * Resend verification email
     */
    public function resendVerification(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'email' => 'required|email',
            ]);

            $user = User::where('email', $validatedData['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            if ($user->email_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is already verified.',
                ], 400);
            }

            // Generate new token
            $verificationToken = bin2hex(random_bytes(32));
            $verificationTokenExpiry = Carbon::now()->addHours(24);

            $user->verification_token = $verificationToken;
            $user->verification_token_expiry = $verificationTokenExpiry;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Verification email will be resent.',
                'data' => [
                    'verification_token' => $verificationToken,
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your input and try again.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Resend verification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification email.',
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

            // Check if email is verified
            if (!$user->email_verified) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email before logging in. Check your inbox for the verification link.',
                    'email_verified' => false,
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful! Welcome back!',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'city' => $user->city,
                        'zip_code' => $user->zip_code,
                        'role' => $user->role,
                        'email_verified' => $user->email_verified,
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
            Log::error('Login error: ' . $e->getMessage());

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
                        'address' => $user->address,
                        'city' => $user->city,
                        'zip_code' => $user->zip_code,
                        'role' => $user->role,
                        'email_verified' => $user->email_verified,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());

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
            Log::error('Logout error: ' . $e->getMessage());

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
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:10',
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
                        'address' => $user->address,
                        'city' => $user->city,
                        'zip_code' => $user->zip_code,
                        'role' => $user->role,
                        'email_verified' => $user->email_verified,
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
            Log::error('Update profile error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
            ], 500);
        }
    }
}