<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CaseStudyController;
use App\Http\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health Check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Lumè Bean and Bar API is running!',
        'timestamp' => now()->toISOString(),
    ]);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->post('/register', [AuthController::class, 'register']);

    // Limit login attempts to 5 per minute per IP
    Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});

// Contact Routes
Route::prefix('contacts')->group(function () {
    Route::post('/', [ContactController::class, 'store']);
    Route::get('/', [ContactController::class, 'index']);
    Route::get('/today-count', [ContactController::class, 'todayCount']);
    Route::get('/{contact}', [ContactController::class, 'show']);
    Route::delete('/{contact}', [ContactController::class, 'destroy']);
});

// Public Testimonials
Route::get('/testimonials', [TestimonialController::class, 'index']);

// Dashboard Analytics
Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);



// Protected Routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // User Management
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update']);
Route::post('/testimonials', [TestimonialController::class, 'store']);
Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy']);


// Settings Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings', [SettingController::class, 'show']);
    Route::put('/settings', [SettingController::class, 'update']);
});

//Services
Route::middleware(['auth:sanctum'])
    ->group(function () {
        Route::apiResource('services', ServiceController::class);
    });

// Results
Route::get('/cases', [CaseStudyController::class, 'index']);
Route::get('/cases/{id}', [CaseStudyController::class, 'show']);

Route::post('/cases', [CaseStudyController::class, 'store']);
Route::put('/cases/{id}', [CaseStudyController::class, 'update']);
Route::delete('/cases/{id}', [CaseStudyController::class, 'destroy']);


// inventory
Route::apiResource('inventories', InventoryController::class);
