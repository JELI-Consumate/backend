<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\AuthResultData;
use App\Data\Auth\RegisterData;
use App\Models\User;
use App\Notifications\Auth\OtpVerificationNotification;
use App\Notifications\Auth\PasswordResetOtpNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class AuthService
{
    public const string INVALID_CREDENTIALS = 'invalid_credentials';

    public const string EMAIL_NOT_VERIFIED = 'email_not_verified';

    public const string INVALID_OTP = 'invalid_otp';

    public const string INVALID_RESET_OTP = 'invalid_reset_otp';

    private const int OTP_LENGTH = 6;

    private const int OTP_TTL_MINUTES = 10;

    private const int OTP_MAX_ATTEMPTS = 5;

    /**
     * No token here on purpose: the app doesn't log the user in until they
     * come back with the OTP (see verifyOtp) — matches the mobile flow where
     * register() only lands on the OTP-entry screen, never on a session.
     */
    public function register(RegisterData $data): User
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'date_of_birth' => $data->dateOfBirth,
            'password' => $data->password,
        ]);

        $this->generateAndSendOtp($user);

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * @return AuthResultData|self::INVALID_CREDENTIALS|self::EMAIL_NOT_VERIFIED
     */
    public function issueToken(User $user, string $password): AuthResultData|string
    {
        if (! Hash::check($password, $user->password)) {
            return self::INVALID_CREDENTIALS;
        }

        if (! $user->hasVerifiedEmail()) {
            return self::EMAIL_NOT_VERIFIED;
        }

        return new AuthResultData($user, $user->createToken('auth')->plainTextToken);
    }

    /**
     * @return AuthResultData|self::INVALID_OTP
     */
    public function verifyOtp(string $email, string $otp): AuthResultData|string
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return self::INVALID_OTP;
        }

        $record = $user->emailVerificationOtp;

        if ($record === null || $record->isExpired()) {
            return self::INVALID_OTP;
        }

        if ($record->attempts >= self::OTP_MAX_ATTEMPTS) {
            $record->delete();

            return self::INVALID_OTP;
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');

            return self::INVALID_OTP;
        }

        $record->delete();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return new AuthResultData($user, $user->createToken('auth')->plainTextToken);
    }

    /**
     * Always a no-op for unknown/already-verified emails so the endpoint
     * can't be used to enumerate registered addresses.
     */
    public function resendOtp(string $email): void
    {
        $user = $this->findByEmail($email);

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $this->generateAndSendOtp($user);
        }
    }

    /**
     * Only one OTP is ever valid per user — generating a new one invalidates
     * whatever was issued before (register, or an earlier resend).
     */
    private function generateAndSendOtp(User $user): void
    {
        $otp = str_pad((string) random_int(0, 10 ** self::OTP_LENGTH - 1), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        $user->emailVerificationOtp()->delete();

        $user->emailVerificationOtp()->create([
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        $user->notify(new OtpVerificationNotification($otp, self::OTP_TTL_MINUTES));
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Always a no-op for unknown emails so the endpoint can't be used to
     * enumerate registered addresses.
     */
    public function sendPasswordResetOtp(string $email): void
    {
        $user = $this->findByEmail($email);

        if ($user !== null) {
            $this->generateAndSendPasswordResetOtp($user);
        }
    }

    /**
     * @return true|self::INVALID_RESET_OTP
     */
    public function resetPassword(string $email, string $otp, string $password): bool|string
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return self::INVALID_RESET_OTP;
        }

        $record = $user->passwordResetOtp;

        if ($record === null || $record->isExpired()) {
            return self::INVALID_RESET_OTP;
        }

        if ($record->attempts >= self::OTP_MAX_ATTEMPTS) {
            $record->delete();

            return self::INVALID_RESET_OTP;
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');

            return self::INVALID_RESET_OTP;
        }

        $record->delete();

        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()->delete();

        event(new PasswordReset($user));

        return true;
    }

    /**
     * Only one reset OTP is ever valid per user — generating a new one
     * invalidates whatever was issued before (an earlier forgot-password call).
     */
    private function generateAndSendPasswordResetOtp(User $user): void
    {
        $otp = str_pad((string) random_int(0, 10 ** self::OTP_LENGTH - 1), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        $user->passwordResetOtp()->delete();

        $user->passwordResetOtp()->create([
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        $user->notify(new PasswordResetOtpNotification($otp, self::OTP_TTL_MINUTES));
    }
}
