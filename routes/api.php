<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BadgeController;
use App\Http\Controllers\Api\V1\EmpowermentIndexController;
use App\Http\Controllers\Api\V1\JourneyController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\ModulePageController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use App\Http\Controllers\Api\V1\ReflectionEntryController;
use App\Http\Controllers\Api\V1\SectorController;
use App\Http\Controllers\Api\V1\SimulationAttemptController;
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

        Route::post('/module-pages/{id}/complete', [ProgressController::class, 'complete']);
        Route::patch('/module-pages/{id}/position', [ProgressController::class, 'position']);
        Route::get('/progress/sectors/{slug}', [ProgressController::class, 'sectorProgress']);
        Route::get('/progress/journeys/{id}', [ProgressController::class, 'journeyProgress']);
        Route::get('/progress/summary', [ProgressController::class, 'summary']);

        Route::get('/quizzes/{id}', [QuizAttemptController::class, 'showQuiz']);
        Route::post('/quizzes/{id}/attempts', [QuizAttemptController::class, 'startAttempt']);
        Route::post('/quiz-attempts/{id}/submit', [QuizAttemptController::class, 'submit'])->middleware('throttle:20,1');
        Route::get('/quiz-attempts/{id}', [QuizAttemptController::class, 'showAttempt']);
        Route::get('/sectors/{slug}/pretest', [QuizAttemptController::class, 'pretest']);
        Route::get('/sectors/{slug}/posttest', [QuizAttemptController::class, 'posttest']);

        Route::get('/simulations/{id}', [SimulationAttemptController::class, 'show']);
        Route::post('/simulations/{id}/attempts', [SimulationAttemptController::class, 'startAttempt']);
        Route::post('/simulation-attempts/{id}/submit', [SimulationAttemptController::class, 'submit'])->middleware('throttle:20,1');

        Route::get('/reflections/{id}', [ReflectionEntryController::class, 'show']);
        Route::put('/reflections/{id}/entries', [ReflectionEntryController::class, 'updateEntries']);

        Route::get('/badges', [BadgeController::class, 'index']);
        Route::get('/empowerment-index', [EmpowermentIndexController::class, 'index']);
    });
});
