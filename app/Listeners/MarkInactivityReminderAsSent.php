<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Collection;
use Kreait\Firebase\Messaging\MulticastSendReport;
use NotificationChannels\Fcm\FcmChannel;

final readonly class MarkInactivityReminderAsSent
{
    public function handle(NotificationSent $event): void
    {
        if (! $event->notification instanceof InactivityReminderNotification) {
            return;
        }

        if ($event->channel !== FcmChannel::class) {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        $reports = $event->response;

        if (! $reports instanceof Collection) {
            return;
        }

        $hasSuccess = $reports->contains(
            fn (MulticastSendReport $report): bool => $report->successes()->count() > 0,
        );

        if (! $hasSuccess) {
            return;
        }

        $event->notifiable->update(['last_inactive_notified_at' => now()]);
    }
}
