<?php

use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\InvalidSubmissionException;
use App\Exceptions\JourneyLockedException;
use App\Exceptions\ModuleLockedException;
use App\Exceptions\QuizNotEligibleException;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['verified' => EnsureEmailIsVerified::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (EmailNotVerifiedException $e) => ApiResponse::error(
            $e->getMessage(),
            403,
            code: 'EMAIL_NOT_VERIFIED',
        ));

        $exceptions->render(fn (JourneyLockedException $e) => ApiResponse::error(
            $e->getMessage(),
            403,
            ['journey' => ['Selesaikan journey sebelumnya terlebih dahulu.']],
            'JOURNEY_LOCKED',
        ));

        $exceptions->render(fn (ModuleLockedException $e) => ApiResponse::error(
            $e->getMessage(),
            403,
            ['module' => ['Selesaikan modul sebelumnya terlebih dahulu.']],
            'MODULE_LOCKED',
        ));

        $exceptions->render(fn (QuizNotEligibleException $e) => ApiResponse::error(
            $e->getMessage(),
            403,
            code: 'QUIZ_NOT_ELIGIBLE',
        ));

        $exceptions->render(fn (InvalidSubmissionException $e) => ApiResponse::error(
            $e->getMessage(),
            409,
            code: 'ATTEMPT_ALREADY_COMPLETED',
        ));
    })->create();
