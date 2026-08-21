<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\JourneyProgressRecalculated;
use App\Services\Progress\ProgressService;

/**
 * BR-15: dipicu tiap kali journey_progress berubah (bukan cuma saat 100%),
 * karena progres sektor perlu naik bertahap seiring user mengerjakan halaman
 * di journey mana pun.
 */
final readonly class RecalculateSectorProgress
{
    public function __construct(private ProgressService $progress) {}

    public function handle(JourneyProgressRecalculated $event): void
    {
        $this->progress->recalculateSector($event->user, $event->journey->sector);
    }
}
