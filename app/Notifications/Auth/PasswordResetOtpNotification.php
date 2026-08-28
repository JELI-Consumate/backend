<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    /**
     * Both public so tests can assert on them via
     * Notification::assertSentTo(..., function ($notification) { ... }).
     * $ttlMinutes is passed in (not hardcoded here) so the email text can
     * never drift from AuthService::OTP_TTL_MINUTES, the actual value used
     * to set expires_at.
     */
    public function __construct(
        public readonly string $otp,
        public readonly int $ttlMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Reset Kata Sandi')
            ->line('Gunakan kode berikut untuk mereset kata sandi akun kamu:')
            ->line("**{$this->otp}**")
            ->line("Kode ini berlaku selama {$this->ttlMinutes} menit. Jangan bagikan kode ini ke siapa pun, termasuk pihak yang mengaku dari kami.")
            ->line('Abaikan email ini jika kamu tidak meminta reset kata sandi.');
    }
}
