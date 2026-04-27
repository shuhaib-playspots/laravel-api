<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| OTP flow (public):
|   POST  /api/auth/send-otp          → send 6-digit OTP to email
|   POST  /api/auth/verify-otp        → verify OTP
|                                         existing user  → { status: 'authenticated', token, user, device }
|                                         new user       → { status: 'registration_required', registration_token }
|   POST  /api/auth/complete-profile  → new user fills name, returns auth token
|
| Protected routes require a Bearer token from Sanctum.
|   PUT   /api/auth/profile           → partial update of name, mobile, gender, dob
|
*/

// Public OTP routes — strict rate limiting
Route::prefix('auth')->middleware(['throttle:auth'])->group(function () {
    Route::post('send-otp',          [AuthController::class, 'sendOtp']);
    Route::post('verify-otp',        [AuthController::class, 'verifyOtp']);
    Route::post('complete-profile',  [AuthController::class, 'completeProfile']);
});

// Protected routes
Route::prefix('auth')->middleware(['auth:sanctum'])->group(function () {
    Route::get('me',                         [AuthController::class, 'me']);
    Route::put('profile',                    [AuthController::class, 'updateProfile']);
    Route::post('logout',                    [AuthController::class, 'logout']);
    Route::post('logout-all',                [AuthController::class, 'logoutAll']);
    Route::post('refresh',                   [AuthController::class, 'refresh']);
    Route::get('devices',                    [AuthController::class, 'devices']);
    Route::delete('devices/{tokenId}',       [AuthController::class, 'revokeDevice'])
         ->whereNumber('tokenId');
});
