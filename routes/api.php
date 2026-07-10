<?php

use App\Http\Controllers\Api\V1\AgencyController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CourierController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EarningController;
use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'message' => 'CRMLog API is running',
        'version' => '1.0.0',
    ]));

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/businesses', [BusinessController::class, 'index']);
        Route::post('/businesses', [BusinessController::class, 'store']);
        Route::get('/businesses/{id}', [BusinessController::class, 'show'])->whereNumber('id');
        Route::put('/businesses/{id}', [BusinessController::class, 'update'])->whereNumber('id');

        Route::get('/couriers', [CourierController::class, 'index']);
        Route::post('/couriers', [CourierController::class, 'store']);
        Route::get('/couriers/{id}', [CourierController::class, 'show'])->whereNumber('id');
        Route::put('/couriers/{id}', [CourierController::class, 'update'])->whereNumber('id');

        Route::get('/agencies', [AgencyController::class, 'index']);
        Route::post('/agencies', [AgencyController::class, 'store']);
        Route::get('/agencies/{id}', [AgencyController::class, 'show'])->whereNumber('id');
        Route::put('/agencies/{id}', [AgencyController::class, 'update'])->whereNumber('id');

        Route::get('/earnings', [EarningController::class, 'index']);
        Route::post('/earnings', [EarningController::class, 'store']);
        Route::get('/earnings/{id}', [EarningController::class, 'show'])->whereNumber('id');
        Route::post('/earnings/{id}/approve', [EarningController::class, 'approve'])->whereNumber('id');

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unreadCount']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    });
});
