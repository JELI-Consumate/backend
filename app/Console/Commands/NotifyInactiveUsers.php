<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Notification\InactivityNotificationService;
use Illuminate\Console\Command;

final class NotifyInactiveUsers extends Command
{
    protected $signature = 'app:notify-inactive-users';

    protected $description = 'Kirim push notification ke user yang tidak aktif >=24 jam';

    public function handle(InactivityNotificationService $service): int
    {
        $service->notifyInactiveUsers();

        $this->info('Selesai kirim notifikasi inaktivitas.');

        return self::SUCCESS;
    }
}
