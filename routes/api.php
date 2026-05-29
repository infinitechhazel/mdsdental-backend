<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
<<<<<<< HEAD
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\BookingController;
=======
>>>>>>> 7be0bdfd869870c656e7bc108e0d9a71f501ef93
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CaseStudyController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BookingController;

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
        'message' => 'API is running!',
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

<<<<<<< HEAD
// Reservation Routes
Route::prefix('reservations')->group(function () {
    // Public routes - MUST be before dynamic routes
    Route::get('/occupied', [ReservationController::class, 'getOccupiedTables']);
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/', [ReservationController::class, 'index']);
    Route::get('/booked-slots', [ReservationController::class, 'getBookedSlots']);

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/upcoming', [ReservationController::class, 'upcoming']);
        Route::get('/past', [ReservationController::class, 'past']);
        Route::get('/{reservation}', [ReservationController::class, 'show']);
        Route::put('/{reservation}', [ReservationController::class, 'update']);
        Route::patch('/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/{reservation}', [ReservationController::class, 'destroy']);
    });
});

// Booking Routes
Route::prefix('bookings')->group(function () {
    Route::post('/', [BookingController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::get('/{booking}', [BookingController::class, 'show']);
        Route::put('/{booking}', [BookingController::class, 'update']);
        Route::patch('/{booking}', [BookingController::class, 'update']);
        Route::delete('/{booking}', [BookingController::class, 'destroy']);
    });
});

// Public Announcements
Route::apiResource('announcements', AnnouncementController::class);
Route::get('announcements/active', [AnnouncementController::class, 'getActive']);

// Public Products Routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/{product}', [ProductController::class, 'show']);
});

=======
>>>>>>> 7be0bdfd869870c656e7bc108e0d9a71f501ef93
// Public Testimonials
Route::get('/testimonials', [TestimonialController::class, 'index']);

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


Route::middleware('auth:sanctum')->group(function () {
    // inventory
    Route::apiResource('inventories', InventoryController::class);

    // dashboard (history-based stats)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // booking history (your appointment page)
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);

    // admin action
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    // optional create
    Route::post('/bookings', [BookingController::class, 'store']);
});
