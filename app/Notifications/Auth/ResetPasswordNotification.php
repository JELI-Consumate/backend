<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = sprintf(
            '%s/reset-password?token=%s&email=%s',
            rtrim((string) config('app.frontend_url'), '/'),
            $this->token,
            urlencode($notifiable->getEmailForPasswordReset()),
        );

        return (new MailMessage)
            ->subject('Reset Kata Sandi')
            ->line('Kami menerima permintaan reset kata sandi untuk akun kamu.')
            ->action('Reset Kata Sandi', $url)
            ->line('Link ini berlaku sesuai batas waktu default sistem. Abaikan email ini jika kamu tidak meminta reset kata sandi.');
    }
}
