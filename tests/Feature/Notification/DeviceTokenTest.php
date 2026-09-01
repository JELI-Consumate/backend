<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\DevicePlatform;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_device_token_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/device-tokens', [
            'fcm_token' => 'token-abc',
            'platform' => DevicePlatform::Android->value,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'token-abc',
            'platform' => DevicePlatform::Android->value,
        ]);
    }

    public function test_registering_the_same_token_twice_does_not_create_a_duplicate_row(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/device-tokens', [
            'fcm_token' => 'token-abc',
            'platform' => DevicePlatform::Android->value,
        ])->assertStatus(201);

        $this->actingAs($user)->postJson('/api/v1/device-tokens', [
            'fcm_token' => 'token-abc',
            'platform' => DevicePlatform::Android->value,
        ])->assertStatus(201);

        $this->assertSame(1, DeviceToken::query()->where('fcm_token', 'token-abc')->count());
    }

    public function test_rejects_request_without_fcm_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/device-tokens', [
            'platform' => DevicePlatform::Android->value,
        ])->assertStatus(422);
    }

    public function test_rejects_invalid_platform_value(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/device-tokens', [
            'fcm_token' => 'token-abc',
            'platform' => 'windows',
        ])->assertStatus(422);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/device-tokens', [
            'fcm_token' => 'token-abc',
            'platform' => DevicePlatform::Android->value,
        ])->assertStatus(401);
    }
}
