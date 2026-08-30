<?php

use App\Console\Commands\NotifyInactiveUsers;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tiap 3 jam, bukan hourly -- lihat feature-notification.md §7.4.
// Trade-off: delay maksimum notif nyampe ke user naik dari <1 jam ke <3 jam
// setelah user genap 24 jam inaktif.
Schedule::command(NotifyInactiveUsers::class)->cron('0 */3 * * *');
