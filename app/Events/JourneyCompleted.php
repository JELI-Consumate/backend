<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Journey;
use App\Models\User;

/**
 * Fire hanya saat journey_progress.status berubah MENJADI completed (bukan
 * setiap rekalkulasi). Dikonsumsi listener AwardJourneyBadge di Fase 8 — belum
 * ada listener terpasang untuk event ini di Fase 4.
 */
final readonly class JourneyCompleted
{
    public function __construct(
        public User $user,
        public Journey $journey,
    ) {}
}
