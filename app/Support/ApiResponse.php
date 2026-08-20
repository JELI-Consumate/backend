<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null, ?string $code = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ], $status);
    }
}
