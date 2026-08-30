<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DevicePlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceToken\StoreDeviceTokenRequest;
use App\Services\Notification\DeviceTokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class DeviceTokenController extends Controller
{
    public function __construct(private readonly DeviceTokenService $deviceTokens) {}

    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $token = $this->deviceTokens->registerToken(
            $request->user(),
            $request->string('fcm_token')->toString(),
            $request->enum('platform', DevicePlatform::class),
        );

        return ApiResponse::success(['id' => $token->id], status: 201);
    }
}
