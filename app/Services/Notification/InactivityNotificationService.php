<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\User;
use App\Notifications\InactivityReminderNotification;

final readonly class InactivityNotificationService
{
    public function notifyInactiveUsers(): void
    {
        User::query()
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '<=', now()->subHours(24))
            ->where(function ($query): void {
                $query->whereNull('last_inactive_notified_at')
                    ->orWhereColumn('last_inactive_notified_at', '<', 'last_active_at');
            })
            ->whereHas('deviceTokens')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $this->sendTo($user);
                }
            });
    }

    private function sendTo(User $user): void
    {
        // `last_inactive_notified_at` di-update oleh
        // App\Listeners\MarkInactivityReminderAsSent, dipicu event
        // NotificationSent setelah minimal 1 device sukses menerima push.
        // Tidak diupdate di sini karena notifikasi dikirim async (ShouldQueue)
        // -- kalau semua device gagal, user harus tetap bisa dicoba lagi.
        $user->notify(new InactivityReminderNotification);
    }
}
