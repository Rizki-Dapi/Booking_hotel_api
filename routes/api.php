<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1');

Route::get('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotels/recommended', [HotelController::class, 'recommended']);
Route::get('/hotels/{hotel}', [HotelController::class, 'show']);
Route::get('/hotels/{hotelId}/reviews', [ReviewController::class, 'index']);

Route::get('/room-types', [RoomTypeController::class, 'index']);
Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);

Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

Route::middleware(['jwt.auth', 'role:client'])->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/history', [BookingController::class, 'history']);
    Route::post('/bookings', [BookingController::class, 'store']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
});

Route::middleware('jwt.auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::delete('/auth/account', [AuthController::class, 'deleteAccount']);
    Route::post('/auth/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1');

    Route::middleware('booking.owner')->group(function () {
        Route::get('/bookings/{booking:booking_code}', [BookingController::class, 'show']);
        Route::put('/bookings/{booking:booking_code}/cancel', [BookingController::class, 'cancel']);
        Route::put('/bookings/{booking:booking_code}/reschedule', [BookingController::class, 'reschedule']);
    });
});

Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::get('/admin/bookings', [BookingController::class, 'adminIndex']);
    Route::get('/admin/bookings/{booking:booking_code}', [BookingController::class, 'show']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::put('/users/{user}/role', [UserController::class, 'assignRole']);

    Route::post('/hotels', [HotelController::class, 'store']);
    Route::put('/hotels/{hotel}', [HotelController::class, 'update']);
    Route::delete('/hotels/{hotel}', [HotelController::class, 'destroy']);

    Route::post('/room-types', [RoomTypeController::class, 'store']);
    Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update']);
    Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy']);

    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
});
