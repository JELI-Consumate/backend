<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Sector;
use App\Models\User;

final readonly class JourneyAccessService
{
    /**
     * BR-01: journey dengan order terkecil di sektornya selalu terbuka; journey
     * berikutnya terbuka hanya kalau journey sebelumnya (order - 1) sudah completed.
     */
    public function isUnlocked(User $user, Journey $journey): bool
    {
        $previous = Journey::query()
            ->where('sector_id', $journey->sector_id)
            ->where('order', '<', $journey->order)
            ->orderByDesc('order')
            ->first();

        if ($previous === null) {
            return true;
        }

        return JourneyProgress::query()
            ->where('user_id', $user->id)
            ->where('journey_id', $previous->id)
            ->where('status', ProgressStatus::Completed)
            ->exists();
    }

    /**
     * Versi bulk untuk endpoint daftar journey — memuat seluruh journey_progress
     * user di sektor ini sekali di awal, lalu menentukan status lock di memori
     * (dilarang panggil isUnlocked() di dalam loop per journey).
     *
     * @return array<int, bool> keyed by journey_id
     */
    public function unlockedMapForSector(User $user, Sector $sector): array
    {
        $journeys = $sector->journeys()->orderBy('order')->get(['id', 'order']);

        $completedJourneyIds = JourneyProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('journey_id', $journeys->pluck('id'))
            ->where('status', ProgressStatus::Completed)
            ->pluck('journey_id')
            ->all();

        $map = [];
        $previousCompleted = true;

        foreach ($journeys as $journey) {
            $map[$journey->id] = $previousCompleted;
            $previousCompleted = in_array($journey->id, $completedJourneyIds, true);
        }

        return $map;
    }
}
