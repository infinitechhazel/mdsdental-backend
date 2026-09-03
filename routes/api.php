<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DentalCaseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Facilities
|--------------------------------------------------------------------------
*/

Route::get('/facilities', [FacilityController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/facilities', [FacilityController::class, 'store']);
    Route::put('/facilities/{facility}', [FacilityController::class, 'update']);
    Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| About
|--------------------------------------------------------------------------
*/

Route::get('/about', [AboutController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/about', [AboutController::class, 'store']);

    Route::post('/about/timeline', [AboutController::class, 'storeTimeline']);
    Route::put('/about/timeline/{id}', [AboutController::class, 'updateTimeline']);
    Route::delete('/about/timeline/{id}', [AboutController::class, 'destroyTimeline']);

    Route::post('/about/tech', [AboutController::class, 'storeTech']);
    Route::put('/about/tech/{id}', [AboutController::class, 'updateTech']);
    Route::delete('/about/tech/{id}', [AboutController::class, 'destroyTech']);
});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', fn() => response()->json([
    'success' => true,
    'message' => 'API is running!',
    'timestamp' => now()->toISOString(),
]));

/*
|--------------------------------------------------------------------------
| Contacts
|--------------------------------------------------------------------------
*/

Route::prefix('contacts')->group(function () {
    Route::post('/', [ContactController::class, 'store']);
    Route::get('/', [ContactController::class, 'index']);
    Route::get('/today-count', [ContactController::class, 'todayCount']);
    Route::get('/{contact}', [ContactController::class, 'show']);
    Route::delete('/{contact}', [ContactController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Dental Cases
|--------------------------------------------------------------------------
*/

Route::get('/cases', [DentalCaseController::class, 'index']);
Route::get('/cases/{dentalCase}', [DentalCaseController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Testimonials
|--------------------------------------------------------------------------
*/

Route::get('/testimonials', [TestimonialController::class, 'index']);
Route::post('/testimonials', [TestimonialController::class, 'store']);
Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update']);
Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Services - Public
|--------------------------------------------------------------------------
*/

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Public Bookings
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get(
        '/bookings/booked-slots',
        [BookingController::class, 'getBookedSlots']
    );
});

/*
|--------------------------------------------------------------------------
| Branches - Public
|--------------------------------------------------------------------------
|
| GET /api/branches
| GET /api/branches/{branch}
|
*/

Route::get('/branches', [BranchController::class, 'index']);
Route::get('/branches/{branch}', [BranchController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Branch Images - Public
|--------------------------------------------------------------------------
|
| GET /api/branch-images
| GET /api/branch-images/{imageId}
|
| Optional filters:
|
| ?branch_id=2
| ?type=clinic
|
*/

Route::get('/branch-images', [BranchImageController::class, 'index']);
Route::get('/branch-images/{imageId}', [BranchImageController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Events - Public
|--------------------------------------------------------------------------
*/

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{eventId}', [EventController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->post(
        '/register',
        [AuthController::class, 'register']
    );

    Route::middleware('throttle:5,1')->post(
        '/login',
        [AuthController::class, 'login']
    );

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated User
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get(
    '/user',
    fn(Request $request) => $request->user()
);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Services - Admin
    |--------------------------------------------------------------------------
    */

    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    Route::apiResource('inventories', InventoryController::class);

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Bookings
    |--------------------------------------------------------------------------
    */

    Route::get('/my-bookings', [BookingController::class, 'myBookings']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

    Route::patch(
        '/bookings/{id}/status',
        [BookingController::class, 'updateStatus']
    );

    /*
    |--------------------------------------------------------------------------
    | Branches - Admin
    |--------------------------------------------------------------------------
    */

    Route::post('/branches', [BranchController::class, 'store']);
    Route::put('/branches/{branch}', [BranchController::class, 'update']);
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Branch Images - Admin
    |--------------------------------------------------------------------------
    |
    | Upload:
    | POST /api/branches/{branchId}/images
    |
    | Update:
    | PUT /api/branch-images/{imageId}
    |
    | Delete:
    | DELETE /api/branch-images/{imageId}
    |
    */

    Route::post(
        '/branches/{branchId}/images',
        [BranchImageController::class, 'store']
    );

    Route::put(
        '/branch-images/{imageId}',
        [BranchImageController::class, 'update']
    );

    Route::delete(
        '/branch-images/{imageId}',
        [BranchImageController::class, 'destroy']
    );

    /*
    |--------------------------------------------------------------------------
    | Events - Admin
    |--------------------------------------------------------------------------
    */

    Route::post('/events', [EventController::class, 'store']);

    Route::post('/events/{eventId}', [
        EventController::class,
        'update',
    ]);

    Route::delete(
        '/events/{eventId}/images/{imageIndex}',
        [EventController::class, 'deleteImage']
    );

    Route::delete(
        '/events/{eventId}',
        [EventController::class, 'destroy']
    );

    /// Dental Cases - Admin
    Route::post('/cases', [DentalCaseController::class, 'store']);
    Route::put('/cases/{dentalCase}', [DentalCaseController::class, 'update']);
    Route::patch('/cases/{dentalCase}', [DentalCaseController::class, 'update']);
    Route::delete('/cases/{dentalCase}', [DentalCaseController::class, 'destroy']);
});
