<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Journey;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;

final readonly class JourneyAccessService
{
    /**
     * BR-01: journey tidak lagi sequential per-order. Journey manapun di sektor
     * terbuka asal user sudah menyelesaikan survei pretest sektor tersebut
     * (SectorProgress.pretest_survey_completed_at terisi).
     */
    public function isUnlocked(User $user, Journey $journey): bool
    {
        return $this->hasCompletedPretestSurvey($user, $journey->sector_id);
    }

    /**
     * Versi bulk untuk endpoint daftar journey — satu query untuk seluruh
     * journey di sektor ini, semua bernilai sama (tidak lagi dirantai per-order).
     *
     * @return array<int, bool> keyed by journey_id
     */
    public function unlockedMapForSector(User $user, Sector $sector): array
    {
        $unlocked = $this->hasCompletedPretestSurvey($user, $sector->id);

        return $sector->journeys()
            ->orderBy('order')
            ->pluck('id')
            ->mapWithKeys(fn (string $journeyId) => [$journeyId => $unlocked])
            ->all();
    }

    private function hasCompletedPretestSurvey(User $user, string $sectorId): bool
    {
        return SectorProgress::query()
            ->where('user_id', $user->id)
            ->where('sector_id', $sectorId)
            ->whereNotNull('pretest_survey_completed_at')
            ->exists();
    }
}
