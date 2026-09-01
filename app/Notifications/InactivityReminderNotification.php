<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

final class InactivityReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(mixed $nitifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm(mixed $notifiable): FcmMessage
    {
        return (new FcmMessage(notification: new FcmNotification(
            title: 'Ayo kembali belajar',
            body: 'Yuk lanjutikan materi yang tertunda !!'
        )
        )
        )->data(['cta' => 'resume_progress']);
    }
}
