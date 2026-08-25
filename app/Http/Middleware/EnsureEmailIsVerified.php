<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\EmailNotVerifiedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces Laravel's default 'verified' middleware alias so an unverified
 * user gets the app's standard ApiResponse envelope (via
 * EmailNotVerifiedException) instead of a bare 403 JSON body.
 */
final class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException;
        }

        return $next($request);
    }
}
