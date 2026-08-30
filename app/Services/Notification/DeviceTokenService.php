<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\DevicePlatform;
use App\Models\DeviceToken;
use App\Models\User;

final readonly class DeviceTokenService
{
    public function registerToken(User $user, string $fcmToken, DevicePlatform $platform): DeviceToken
    {
        return DeviceToken::query()->updateOrCreate(
            ['fcm_token'=> $fcmToken],
            ['user_id' => $user->id, 'platform' => $platform]
        );
    }
}