<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\JourneyController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\ModulePageController;
use App\Http\Controllers\Api\V1\SectorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('/google', [AuthController::class, 'google'])->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::patch('/profile', [AuthController::class, 'updateProfile']);
        });
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::get('/sectors', [SectorController::class, 'index']);
        Route::get('/sectors/{slug}', [SectorController::class, 'show']);
        Route::get('/journeys/{id}', [JourneyController::class, 'show']);
        Route::get('/modules/{id}', [ModuleController::class, 'show']);
        Route::get('/module-pages/{id}', [ModulePageController::class, 'show']);
    });
});
