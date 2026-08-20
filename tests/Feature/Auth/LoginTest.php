<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'login2@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'login2@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    /**
     * BR-17: user with password = null (Google-only account) must be rejected
     * with a clear, non-generic message — not allowed to reach Hash::check().
     */
    public function test_br17_google_only_account_cannot_login_with_password(): void
    {
        User::factory()->create([
            'email' => 'google-only@example.com',
            'password' => null,
            'google_id' => 'google-sub-123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'google-only@example.com',
            'password' => 'whatever',
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'GOOGLE_ONLY_ACCOUNT');
    }
}
