<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\OtpVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The 'array' cache driver used in tests lives for the whole process, so
     * without this, route throttling (keyed by IP+path) leaks across test
     * methods in this file — several of which hit /verify-email repeatedly
     * on purpose (e.g. the lockout test).
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_register_sends_otp_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi-verify@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'budi-verify@example.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            OtpVerificationNotification::class,
            fn (OtpVerificationNotification $n) => strlen($n->otp) === 6 && ctype_digit($n->otp),
        );
    }

    public function test_otp_expires_in_ten_minutes_and_email_says_so(): void
    {
        Notification::fake();

        $email = 'otp-ttl@example.com';
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ])->assertStatus(201);

        $user = User::where('email', $email)->firstOrFail();

        // Toleransi kecil buat selisih waktu antara now() saat request dan
        // now() saat assert ini jalan.
        $this->assertEqualsWithDelta(
            now()->addMinutes(10)->timestamp,
            $user->emailVerificationOtp->expires_at->timestamp,
            5,
        );

        Notification::assertSentTo(
            $user,
            OtpVerificationNotification::class,
            function (OtpVerificationNotification $n) use ($user): bool {
                self::assertSame(10, $n->ttlMinutes);

                $lines = [...$n->toMail($user)->introLines, ...$n->toMail($user)->outroLines];
                self::assertTrue(
                    collect($lines)->contains(fn (string $line) => str_contains($line, '10 menit')),
                    'Email OTP harus menyebutkan masa berlaku 10 menit.',
                );

                return true;
            },
        );
    }

    /**
     * Captures the exact OTP the app would've emailed, via Notification::fake.
     */
    private function registerAndCaptureOtp(string $email): string
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ])->assertStatus(201);

        $user = User::where('email', $email)->firstOrFail();

        $otp = null;
        Notification::assertSentTo(
            $user,
            OtpVerificationNotification::class,
            function (OtpVerificationNotification $n) use (&$otp): bool {
                $otp = $n->otp;

                return true;
            },
        );

        return $otp;
    }

    public function test_correct_otp_verifies_email_and_returns_token(): void
    {
        $email = 'otp-correct@example.com';
        $otp = $this->registerAndCaptureOtp($email);

        $response = $this->postJson('/api/v1/auth/verify-email', ['email' => $email, 'otp' => $otp]);

        $response->assertStatus(200)->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertNotNull(User::where('email', $email)->firstOrFail()->email_verified_at);
        $this->assertDatabaseCount('email_verification_otps', 0);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $email = 'otp-wrong@example.com';
        $this->registerAndCaptureOtp($email);

        $response = $this->postJson('/api/v1/auth/verify-email', ['email' => $email, 'otp' => '000000']);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_OTP');
        $this->assertNull(User::where('email', $email)->firstOrFail()->email_verified_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $email = 'otp-expired@example.com';
        $otp = $this->registerAndCaptureOtp($email);

        $user = User::where('email', $email)->firstOrFail();
        $user->emailVerificationOtp()->update(['expires_at' => now()->subMinute()]);

        $response = $this->postJson('/api/v1/auth/verify-email', ['email' => $email, 'otp' => $otp]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_OTP');
    }

    public function test_otp_locks_out_after_too_many_wrong_attempts(): void
    {
        // This test is about the OTP attempt counter in AuthService, not the
        // route-level IP throttle (which — a pre-existing quirk — is shared
        // across *all* unauthenticated /auth/* endpoints, not per-route, so
        // 6 wrong-guess requests here would trip it well before the 5-try
        // OTP lockout even comes into play).
        $this->withoutMiddleware(ThrottleRequests::class);

        $email = 'otp-lockout@example.com';
        $otp = $this->registerAndCaptureOtp($email);

        // 5 wrong tries exhausts the attempt budget and invalidates the OTP...
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/verify-email', ['email' => $email, 'otp' => '000000'])
                ->assertStatus(422);
        }

        // ...so even the correct code no longer works without a resend.
        $response = $this->postJson('/api/v1/auth/verify-email', ['email' => $email, 'otp' => $otp]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_OTP');
        $this->assertNull(User::where('email', $email)->firstOrFail()->email_verified_at);
    }

    public function test_resend_invalidates_the_previous_otp(): void
    {
        $email = 'otp-resend@example.com';
        $oldOtp = $this->registerAndCaptureOtp($email);

        Notification::fake();
        $this->postJson('/api/v1/auth/verify-email/resend', ['email' => $email])->assertStatus(200);

        $user = User::where('email', $email)->firstOrFail();
        $newOtp = null;
        Notification::assertSentTo(
            $user,
            OtpVerificationNotification::class,
            function (OtpVerificationNotification $n) use (&$newOtp): bool {
                $newOtp = $n->otp;

                return true;
            },
        );

        $this->assertDatabaseCount('email_verification_otps', 1);

        // The old code is dead now, even if it happens to match the new one.
        if ($oldOtp === $newOtp) {
            $this->markTestSkipped('Collided by chance with the new OTP — nothing to assert.');
        }
        $this->postJson('/api/v1/auth/verify-email', ['email' => $email, 'otp' => $oldOtp])
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_OTP');

        $this->postJson('/api/v1/auth/verify-email', ['email' => $email, 'otp' => $newOtp])
            ->assertStatus(200);
    }

    public function test_resend_does_not_leak_whether_email_exists(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/verify-email/resend', ['email' => 'nobody@example.com']);

        $response->assertStatus(200);
        Notification::assertNothingSent();
    }

    public function test_resend_is_noop_for_already_verified_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'already-verified@example.com']);

        $response = $this->postJson('/api/v1/auth/verify-email/resend', ['email' => 'already-verified@example.com']);

        $response->assertStatus(200);
        Notification::assertNothingSentTo($user);
    }

    public function test_unverified_user_cannot_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_wrong_password_takes_priority_over_unverified_status(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified2@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified2@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_verified_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'verified@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'verified@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_unverified_user_token_is_rejected_by_protected_content_routes(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/badges');

        $response->assertStatus(403)->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }
}
