<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\MarkInactivityReminderAsSent;
use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use NotificationChannels\Fcm\FcmChannel;
use Tests\TestCase;

final class MarkInactivityReminderAsSentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fitur: setelah minimal 1 device sukses menerima push, user boleh
     * ditandai "sudah dinotif" untuk sesi inaktif ini. Update tidak boleh
     * dilakukan lebih awal (sebelum tahu hasil kirim), karena notifikasi
     * dikirim async lewat queue (ShouldQueue).
     */
    public function test_marks_user_notified_when_at_least_one_device_succeeds(): void
    {
        $user = User::factory()->create(['last_inactive_notified_at' => null]);

        $successTarget = MessageTarget::with(MessageTarget::TOKEN, 'alive-token');
        $failedTarget = MessageTarget::with(MessageTarget::TOKEN, 'dead-token');

        $report = MulticastSendReport::withItems([
            SendReport::success($successTarget, []),
            SendReport::failure($failedTarget, NotFound::becauseTokenNotFound('dead-token')),
        ]);

        $event = new NotificationSent(
            $user,
            new InactivityReminderNotification,
            FcmChannel::class,
            Collection::make([$report]),
        );

        app(MarkInactivityReminderAsSent::class)->handle($event);

        $this->assertNotNull($user->fresh()->last_inactive_notified_at);
    }

    public function test_does_not_mark_user_notified_when_every_device_fails(): void
    {
        $user = User::factory()->create(['last_inactive_notified_at' => null]);

        $failedTarget = MessageTarget::with(MessageTarget::TOKEN, 'dead-token');

        $report = MulticastSendReport::withItems([
            SendReport::failure($failedTarget, NotFound::becauseTokenNotFound('dead-token')),
        ]);

        $event = new NotificationSent(
            $user,
            new InactivityReminderNotification,
            FcmChannel::class,
            Collection::make([$report]),
        );

        app(MarkInactivityReminderAsSent::class)->handle($event);

        $this->assertNull($user->fresh()->last_inactive_notified_at);
    }

    public function test_ignores_notifications_other_than_inactivity_reminder(): void
    {
        $user = User::factory()->create(['last_inactive_notified_at' => null]);

        $event = new NotificationSent(
            $user,
            new Notification,
            'mail',
            null,
        );

        app(MarkInactivityReminderAsSent::class)->handle($event);

        $this->assertNull($user->fresh()->last_inactive_notified_at);
    }
}
