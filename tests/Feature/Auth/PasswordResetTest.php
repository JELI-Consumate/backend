<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_notification_for_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reset@example.com']);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_does_not_leak_whether_email_exists(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertStatus(200);

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset2@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset2@example.com',
            'token' => $token,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(200);

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        User::factory()->create(['email' => 'reset3@example.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset3@example.com',
            'token' => 'invalid-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_RESET_TOKEN');
    }

    public function test_reset_password_revokes_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'reset4@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $user->createToken('auth');

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset4@example.com',
            'token' => $token,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
