<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateLastActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_last_active_at_on_first_authenticated_request(): void
    {
        $user = User::factory()->create(['last_active_at' => null]);

        $this->actingAs($user)->getJson('/api/v1/auth/me')->assertOk();

        $this->assertNotNull($user->fresh()->last_active_at);
    }

    /**
     * Throttle 5 menit -- request beruntun tidak boleh nulis ulang tiap kali.
     */
    public function test_does_not_update_last_active_at_within_the_throttle_window(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subMinutes(2)]);
        $originalTimestamp = $user->last_active_at;

        $this->actingAs($user)->getJson('/api/v1/auth/me')->assertOk();

        $this->assertTrue($originalTimestamp->equalTo($user->fresh()->last_active_at));
    }

    public function test_updates_last_active_at_after_the_throttle_window_has_passed(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subMinutes(10)]);
        $originalTimestamp = $user->last_active_at;

        $this->actingAs($user)->getJson('/api/v1/auth/me')->assertOk();

        $this->assertTrue($user->fresh()->last_active_at->greaterThan($originalTimestamp));
    }
}
