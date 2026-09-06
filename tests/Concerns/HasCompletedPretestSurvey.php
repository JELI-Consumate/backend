<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;

trait HasCompletedPretestSurvey
{
    protected function completePretestSurvey(User $user, Sector $sector): SectorProgress
    {
        return SectorProgress::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'pretest_survey_completed_at' => now(),
        ]);
    }
}
