<?php

use App\Http\Controllers\Api\V1\App\AuthController as AppAuthController;
use App\Http\Controllers\Api\V1\App\CheckoutController as AppCheckoutController;
use App\Http\Controllers\Api\V1\App\CourseController as AppCourseController;
use App\Http\Controllers\Api\V1\App\LibraryController as AppLibraryController;
use App\Http\Controllers\Api\V1\App\MeController as AppMeController;
use App\Http\Controllers\Api\V1\App\OrderController as AppOrderController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api', 'api.token'])->group(function () {
    Route::get('pages', [PageController::class, 'index']);
    Route::get('pages/{slug}', [PageController::class, 'show']);
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{slug}', [PostController::class, 'show']);
    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{slug}', [CourseController::class, 'show']);
});

Route::prefix('v1/app')->middleware(['json', 'app.client'])->group(function () {
    Route::post('auth/otp', [AppAuthController::class, 'sendOtp'])->middleware('throttle:otp-send');
    Route::post('auth/token', [AppAuthController::class, 'token'])->middleware('throttle:otp-verify');

    Route::get('courses', [AppCourseController::class, 'index'])->middleware('throttle:api');
    Route::get('courses/{slug}', [AppCourseController::class, 'show'])->middleware('throttle:api');

    Route::middleware(['auth:sanctum', 'abilities:app', 'api.user', 'throttle:api'])->group(function () {
        Route::delete('auth/token', [AppAuthController::class, 'logout']);
        Route::get('me', [AppMeController::class, 'show']);
        Route::post('orders', [AppCheckoutController::class, 'store']);
        Route::get('orders', [AppOrderController::class, 'index']);
        Route::get('orders/{orderNumber}', [AppOrderController::class, 'show']);
        Route::get('me/courses', [AppLibraryController::class, 'index']);
        Route::get('me/courses/{course}', [AppLibraryController::class, 'show']);
        Route::post('me/courses/{course}/license', [AppLibraryController::class, 'license'])
            ->middleware('throttle:api-license');
    });
});
