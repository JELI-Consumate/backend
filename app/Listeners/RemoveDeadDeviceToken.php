<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\DeviceToken;
use Illuminate\Notifications\Events\NotificationFailed;
use Kreait\Firebase\Messaging\SendReport;
use NotificationChannels\Fcm\FcmChannel;

final readonly class RemoveDeadDeviceToken
{
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel !== FcmChannel::class) {
            return;
        }

        $report = $event->data['report'] ?? null;

        if (! $report instanceof SendReport) {
            return;
        }

        if (! $report->messageTargetWasInvalid() && ! $report->messageWasSentToUnknownToken()) {
            return;
        }

        DeviceToken::query()->where('fcm_token', $report->target()->value())->delete();
    }
}
