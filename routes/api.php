<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CaseStudyController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\FacilityController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/facilities', [FacilityController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    // Facilities CRUD

    Route::post('/facilities', [FacilityController::class, 'store']);
    Route::put('/facilities/{facility}', [FacilityController::class, 'update']);
    Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy']);

    // Alternative: Use RESTful resource routing
    // Route::apiResource('facilities', FacilityController::class);
});

Route::get('/about', [AboutController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/about',            [AboutController::class, 'store']);  // ← POST + store

    Route::post('/about/timeline',        [AboutController::class, 'storeTimeline']);
    Route::put('/about/timeline/{id}',    [AboutController::class, 'updateTimeline']);
    Route::delete('/about/timeline/{id}', [AboutController::class, 'destroyTimeline']);

    Route::post('/about/tech',        [AboutController::class, 'storeTech']);
    Route::put('/about/tech/{id}',    [AboutController::class, 'updateTech']);
    Route::delete('/about/tech/{id}', [AboutController::class, 'destroyTech']);
});
// ── Health Check ──────────────────────────────────────────────────────────────
Route::get('/health', fn() => response()->json([
    'success'   => true,
    'message'   => 'API is running!',
    'timestamp' => now()->toISOString(),
]));

// ── Contacts (public write, no auth needed for form submissions) ──────────────
Route::prefix('contacts')->group(function () {
    Route::post('/',             [ContactController::class, 'store']);
    Route::get('/',              [ContactController::class, 'index']);
    Route::get('/today-count',   [ContactController::class, 'todayCount']);
    Route::get('/{contact}',     [ContactController::class, 'show']);
    Route::delete('/{contact}',  [ContactController::class, 'destroy']);
});

// ── Testimonials ──────────────────────────────────────────────────────────────
Route::get('/testimonials',              [TestimonialController::class, 'index']);
Route::post('/testimonials',             [TestimonialController::class, 'store']);
Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update']);
Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy']);

// ── Case Studies ──────────────────────────────────────────────────────────────
Route::get('/cases',         [CaseStudyController::class, 'index']);
Route::get('/cases/{id}',    [CaseStudyController::class, 'show']);
Route::post('/cases',        [CaseStudyController::class, 'store']);
Route::put('/cases/{id}',    [CaseStudyController::class, 'update']);
Route::delete('/cases/{id}', [CaseStudyController::class, 'destroy']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);

//    (Public) Bookings - Get all occupied time slots within a month
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/bookings/booked-slots', [BookingController::class, 'getBookedSlots']);
});

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->post('/register', [AuthController::class, 'register']);
    Route::middleware('throttle:5,1')->post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',         [AuthController::class, 'me']);
        Route::post('/logout',    [AuthController::class, 'logout']);
        Route::put('/profile',    [AuthController::class, 'updateProfile']);
    });
});

// ── Authenticated user helper ─────────────────────────────────────────────────
Route::middleware('auth:sanctum')->get('/user', fn(Request $r) => $r->user());

// ── Protected routes (auth:sanctum) ──────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── User management ───────────────────────────────────────────────────
    Route::get('/users',        [UserController::class, 'index']);
    Route::get('/users/{id}',   [UserController::class, 'show']);
    Route::put('/users/{id}',   [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // ── Settings ──────────────────────────────────────────────────────────
    Route::get('/settings', [SettingController::class, 'show']);
    Route::put('/settings', [SettingController::class, 'update']);

    // ── Services ──────────────────────────────────────────────────────────
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    // ── Inventory ─────────────────────────────────────────────────────────
    Route::apiResource('inventories', InventoryController::class);

    // ── Dashboard ─────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ── Bookings ──────────────────────────────────────────────────────────

    // User: own bookings only
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);

    // Admin: all bookings (list + create)
    Route::get('/bookings',    [BookingController::class, 'index']);
    Route::post('/bookings',   [BookingController::class, 'store']);

    // Single booking
    Route::get('/bookings/{id}',      [BookingController::class, 'show']);
    Route::put('/bookings/{id}',      [BookingController::class, 'update']);
    Route::delete('/bookings/{id}',   [BookingController::class, 'destroy']);

    // (Admin) approve / reject — PATCH /api/bookings/{id}/status
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
});
