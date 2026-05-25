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
    Route::post('/register', [AuthController::class, 'register']);

    // Limit login attempts to 5 per minute per IP
    Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});
// email verification routes
Route::get('/auth/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification']);

// Contact Routes
Route::prefix('contacts')->group(function () {
    Route::post('/', [ContactController::class, 'store']);
    Route::get('/', [ContactController::class, 'index']);
    Route::get('/today-count', [ContactController::class, 'todayCount']);
    Route::get('/{contact}', [ContactController::class, 'show']);
    Route::delete('/{contact}', [ContactController::class, 'destroy']);
});

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

// Public Announcements
Route::apiResource('announcements', AnnouncementController::class);
Route::get('announcements/active', [AnnouncementController::class, 'getActive']);

// Public Products Routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/{product}', [ProductController::class, 'show']);
});

// Public Testimonials
Route::get('/testimonials', [TestimonialController::class, 'index']);

// Dashboard Analytics
Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);

// Blog Posts Routes
Route::prefix('blog-posts')->group(function () {
    Route::get('/', [BlogPostController::class, 'index']);
    Route::post('/', [BlogPostController::class, 'store']);
    Route::get('/{blogPost}', [BlogPostController::class, 'show']);
    Route::post('/{blogPost}', [BlogPostController::class, 'update']); // For FormData with _method
    Route::put('/{blogPost}', [BlogPostController::class, 'update']);
    Route::delete('/{blogPost}', [BlogPostController::class, 'destroy']);
    Route::post('/video/upload-chunk', [BlogPostController::class, 'uploadVideoChunk']);
});

// Event Routes (Public: index, show, store | Protected: update, destroy)
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/{id}', [EventController::class, 'show']);
    Route::post('/', [EventController::class, 'store']); // Public - anyone can book

    Route::middleware('auth:sanctum')->group(function () {
        Route::patch('/{id}', [EventController::class, 'update']);
        Route::put('/{id}', [EventController::class, 'update']);
        Route::delete('/{id}', [EventController::class, 'destroy']);
    });
});

// Protected Routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Order Routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}', [OrderController::class, 'update']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::get('/order-items', [OrderController::class, 'getOrderItems']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
    // Product Management (admin)

    Route::put('/products/{product}', [ProductController::class, 'update']);
    // Route::post('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete']);

    // User Management
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Announcement Management
    Route::post('/announcements', [AnnouncementController::class, 'store']);

});

Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update']);
Route::post('/testimonials', [TestimonialController::class, 'store']);
Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy']);

Route::post('/products', [ProductController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // Address routes
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::post('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);
});

// Settings Routes
Route::get('/settings', [SettingController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/settings', [SettingController::class, 'update']);
});

// Payment Methods Routes
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);
});

// Support Ticket Routes
Route::apiResource('support-tickets', SupportTicketController::class);
Route::put('/support-tickets/{supportTicket}', [SupportTicketController::class, 'update']);
