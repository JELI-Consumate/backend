<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $threshold = $user->last_active_at?->addMinutes(5);

            if ($threshold === null || now()->greaterThan($threshold)) {
                $user->update(['last_active_at' => now()]);
            }
        }

        return $next($request);
    }
}
