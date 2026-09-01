<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BadgeController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\EmpowermentIndexController;
use App\Http\Controllers\Api\V1\JourneyController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\ModulePageController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use App\Http\Controllers\Api\V1\ReflectionEntryController;
use App\Http\Controllers\Api\V1\SectorController;
use App\Http\Controllers\Api\V1\SectorSurveyController;
use App\Http\Controllers\Api\V1\SimulationAttemptController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('/google', [AuthController::class, 'google'])->middleware('throttle:6,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

        // No auth guard: the user has no session/token yet at this point,
        // only the email+otp they just typed in the app.
        Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:6,1');
        Route::post('/verify-email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::patch('/profile', [AuthController::class, 'updateProfile']);
        });
    });

    // 'verified' requires email_verified_at to be set. In practice no token
    // is ever issued for an unverified user (register() no longer returns
    // one, login()/verifyOtp() both check it first) — this is defense in
    // depth, not the primary gate.
    Route::middleware(['auth:sanctum', 'verified', 'throttle:60,1'])->group(function (): void {
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
        Route::get('/progress/next', [ProgressController::class, 'next']);

        Route::post('/device-tokens', [DeviceTokenController::class, 'store']);

        Route::get('/quizzes/{id}', [QuizAttemptController::class, 'showQuiz']);
        Route::post('/quizzes/{id}/attempts', [QuizAttemptController::class, 'startAttempt']);
        Route::post('/quiz-attempts/{id}/submit', [QuizAttemptController::class, 'submit'])->middleware('throttle:20,1');
        // Cek 1 pertanyaan per panggilan (gaya ujian, lihat QuizScoringService::checkAnswer)
        // -> limit lebih longgar dari submit batch, sama seperti simulation-attempts/check.
        Route::post('/quiz-attempts/{id}/check', [QuizAttemptController::class, 'checkAnswer'])->middleware('throttle:60,1');
        Route::get('/quiz-attempts/{id}', [QuizAttemptController::class, 'showAttempt']);
        Route::get('/sectors/{slug}/pretest', [QuizAttemptController::class, 'pretest']);
        Route::get('/sectors/{slug}/posttest', [QuizAttemptController::class, 'posttest']);

        // Survei eksternal (Google Form) per sektor -- terpisah dari kuis
        // in-app di atas. Link diisi admin lewat Filament; endpoint di bawah
        // cuma menandai self-report user sudah mengisinya (lihat
        // SectorSurveyService, tidak ada verifikasi isi form yang sesungguhnya).
        Route::post('/sectors/{slug}/pretest-survey/complete', [SectorSurveyController::class, 'completePretest']);
        Route::post('/sectors/{slug}/posttest-survey/complete', [SectorSurveyController::class, 'completePosttest']);

        Route::get('/simulations/{id}', [SimulationAttemptController::class, 'show']);
        Route::post('/simulations/{id}/attempts', [SimulationAttemptController::class, 'startAttempt']);
        // Duolingo-style: 1 request per item (bukan 1 batch submit) -> limit lebih longgar dari kuis.
        Route::post('/simulation-attempts/{id}/check', [SimulationAttemptController::class, 'checkAnswer'])->middleware('throttle:60,1');

        Route::get('/reflections/{id}', [ReflectionEntryController::class, 'show']);
        Route::put('/reflections/{id}/entries', [ReflectionEntryController::class, 'updateEntries']);

        Route::get('/badges', [BadgeController::class, 'index']);
        Route::get('/empowerment-index', [EmpowermentIndexController::class, 'index']);
    });
});
