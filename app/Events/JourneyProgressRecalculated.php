<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Journey;
use App\Models\User;

/**
 * Fire tiap kali ProgressService::recalculateJourney() dipanggil (selalu, bukan
 * cuma saat 100% — BR-15). $journey sudah loadMissing('sector') sebelum event
 * ini dibuat, supaya listener bisa akses $journey->sector tanpa lazy load.
 */
final readonly class JourneyProgressRecalculated
{
    public function __construct(
        public User $user,
        public Journey $journey,
    ) {}
}
