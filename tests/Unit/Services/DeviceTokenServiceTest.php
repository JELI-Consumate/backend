<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\DevicePlatform;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Notification\DeviceTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeviceTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_token_creates_a_new_device_token(): void
    {
        $user = User::factory()->create();

        $token = app(DeviceTokenService::class)->registerToken($user, 'token-abc', DevicePlatform::Android);

        $this->assertDatabaseHas('device_tokens', [
            'id' => $token->id,
            'user_id' => $user->id,
            'fcm_token' => 'token-abc',
            'platform' => DevicePlatform::Android->value,
        ]);
    }

    /**
     * Flutter bisa kirim token yang sama berkali-kali (mis. tiap app start) —
     * WAJIB tidak bikin baris baru tiap kali dipanggil dengan token yang sama.
     */
    public function test_register_token_is_idempotent_for_the_same_fcm_token(): void
    {
        $user = User::factory()->create();
        $service = app(DeviceTokenService::class);

        $first = $service->registerToken($user, 'token-abc', DevicePlatform::Android);
        $second = $service->registerToken($user, 'token-abc', DevicePlatform::Android);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DeviceToken::query()->where('fcm_token', 'token-abc')->count());
    }

    /**
     * fcm_token unique lintas user (device dipakai gantian / re-login akun lain
     * di device sama) -- updateOrCreate by fcm_token WAJIB pindahkan kepemilikan
     * ke user baru, bukan gagal karena unique constraint.
     */
    public function test_register_token_reassigns_ownership_when_token_registered_by_a_different_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $service = app(DeviceTokenService::class);

        $service->registerToken($userA, 'shared-token', DevicePlatform::Ios);
        $service->registerToken($userB, 'shared-token', DevicePlatform::Ios);

        $this->assertSame(1, DeviceToken::query()->where('fcm_token', 'shared-token')->count());
        $this->assertDatabaseHas('device_tokens', [
            'fcm_token' => 'shared-token',
            'user_id' => $userB->id,
        ]);
    }

    public function test_register_token_updates_platform_when_it_changes(): void
    {
        $user = User::factory()->create();
        $service = app(DeviceTokenService::class);

        $service->registerToken($user, 'token-abc', DevicePlatform::Android);
        $service->registerToken($user, 'token-abc', DevicePlatform::Ios);

        $this->assertDatabaseHas('device_tokens', [
            'fcm_token' => 'token-abc',
            'platform' => DevicePlatform::Ios->value,
        ]);
    }
}
