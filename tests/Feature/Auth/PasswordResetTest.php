<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\PasswordResetOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The 'array' cache driver used in tests lives for the whole process, so
     * without this, route throttling (keyed by IP+path) leaks across test
     * methods in this file — several of which hit /forgot-password or
     * /reset-password repeatedly on purpose (e.g. the lockout test).
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_forgot_password_sends_otp_notification_for_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reset@example.com']);

        $response->assertStatus(200);

        Notification::assertSentTo(
            $user,
            PasswordResetOtpNotification::class,
            fn (PasswordResetOtpNotification $n) => strlen($n->otp) === 6 && ctype_digit($n->otp),
        );
    }

    public function test_forgot_password_does_not_leak_whether_email_exists(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertStatus(200);

        Notification::assertNothingSent();
    }

    /**
     * Captures the exact OTP the app would've emailed, via Notification::fake.
     */
    private function requestResetAndCaptureOtp(string $email): string
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $email])->assertStatus(200);

        $user = User::where('email', $email)->firstOrFail();

        $otp = null;
        Notification::assertSentTo(
            $user,
            PasswordResetOtpNotification::class,
            function (PasswordResetOtpNotification $n) use (&$otp): bool {
                $otp = $n->otp;

                return true;
            },
        );

        return $otp;
    }

    public function test_user_can_reset_password_with_valid_otp(): void
    {
        $email = 'reset2@example.com';
        User::factory()->create(['email' => $email, 'password' => Hash::make('old-password')]);

        $otp = $this->requestResetAndCaptureOtp($email);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'otp' => $otp,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(200);

        $this->assertTrue(Hash::check('new-password123', User::where('email', $email)->firstOrFail()->password));
        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_reset_password_rejects_wrong_otp(): void
    {
        $email = 'reset3@example.com';
        User::factory()->create(['email' => $email, 'password' => Hash::make('old-password')]);
        $this->requestResetAndCaptureOtp($email);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'otp' => '000000',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_RESET_OTP');
        $this->assertTrue(Hash::check('old-password', User::where('email', $email)->firstOrFail()->password));
    }

    public function test_reset_password_rejects_otp_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'nobody@example.com',
            'otp' => '123456',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_RESET_OTP');
    }

    public function test_reset_password_rejects_expired_otp(): void
    {
        $email = 'reset4@example.com';
        User::factory()->create(['email' => $email, 'password' => Hash::make('old-password')]);
        $otp = $this->requestResetAndCaptureOtp($email);

        $user = User::where('email', $email)->firstOrFail();
        $user->passwordResetOtp()->update(['expires_at' => now()->subMinute()]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'otp' => $otp,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_RESET_OTP');
    }

    public function test_reset_password_locks_out_after_too_many_wrong_attempts(): void
    {
        // This test is about the OTP attempt counter in AuthService, not the
        // route-level IP throttle (which — a pre-existing quirk — is shared
        // across *all* unauthenticated /auth/* endpoints, not per-route, so
        // 6 wrong-guess requests here would trip it well before the 5-try
        // OTP lockout even comes into play).
        $this->withoutMiddleware(ThrottleRequests::class);

        $email = 'reset5@example.com';
        User::factory()->create(['email' => $email, 'password' => Hash::make('old-password')]);
        $otp = $this->requestResetAndCaptureOtp($email);

        $payload = fn (string $otp): array => [
            'email' => $email,
            'otp' => $otp,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ];

        // 5 wrong tries exhausts the attempt budget and invalidates the OTP...
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/reset-password', $payload('000000'))
                ->assertStatus(422);
        }

        // ...so even the correct code no longer works without a fresh request.
        $response = $this->postJson('/api/v1/auth/reset-password', $payload($otp));

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_RESET_OTP');
        $this->assertTrue(Hash::check('old-password', User::where('email', $email)->firstOrFail()->password));
    }

    public function test_forgot_password_invalidates_the_previous_otp(): void
    {
        $email = 'reset6@example.com';
        User::factory()->create(['email' => $email, 'password' => Hash::make('old-password')]);

        $oldOtp = $this->requestResetAndCaptureOtp($email);
        $newOtp = $this->requestResetAndCaptureOtp($email);

        $this->assertDatabaseCount('password_reset_otps', 1);

        // The old code is dead now, even if it happens to match the new one.
        if ($oldOtp === $newOtp) {
            $this->markTestSkipped('Collided by chance with the new OTP — nothing to assert.');
        }

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'otp' => $oldOtp,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertStatus(422)->assertJsonPath('code', 'INVALID_RESET_OTP');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'otp' => $newOtp,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertStatus(200);
    }

    public function test_reset_password_revokes_existing_tokens(): void
    {
        $email = 'reset7@example.com';
        $user = User::factory()->create(['email' => $email, 'password' => Hash::make('old-password')]);
        $user->createToken('auth');

        $otp = $this->requestResetAndCaptureOtp($email);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'otp' => $otp,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
