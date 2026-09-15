<?php

use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\InvalidQuizContentException;
use App\Exceptions\InvalidSubmissionException;
use App\Exceptions\JourneyLockedException;
use App\Exceptions\ModuleLockedException;
use App\Exceptions\QuizNotEligibleException;
use App\Exceptions\SurveyNotConfiguredException;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\UpdateLastActive;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['verified' => EnsureEmailIsVerified::class]);
        $middleware->api(append: [UpdateLastActive::class]);

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : route('login'),
        );
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
            ['journey' => [$e->getMessage()]],
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
            code: $e->apiCode,
        ));

        $exceptions->render(fn (InvalidSubmissionException $e) => ApiResponse::error(
            $e->getMessage(),
            409,
            code: 'ATTEMPT_ALREADY_COMPLETED',
        ));

        $exceptions->render(fn (SurveyNotConfiguredException $e) => ApiResponse::error(
            $e->getMessage(),
            422,
            code: 'SURVEY_NOT_CONFIGURED',
        ));

        $exceptions->render(fn (InvalidQuizContentException $e) => ApiResponse::error(
            $e->getMessage(),
            422,
            code: 'INVALID_QUIZ_CONTENT',
        ));

        // Fallback envelope untuk exception bawaan Laravel supaya semua error API
        // (bukan cuma custom exception di atas) konsisten pakai ApiResponse + code.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage(),
                $e->status,
                $e->errors(),
                'VALIDATION_ERROR',
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage(),
                401,
                code: 'UNAUTHENTICATED',
            );
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage(),
                403,
                code: 'FORBIDDEN',
            );
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Data yang diminta tidak ditemukan.',
                404,
                code: 'NOT_FOUND',
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Data yang diminta tidak ditemukan.',
                404,
                code: 'NOT_FOUND',
            );
        });
    })->create();
