<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\Badge;
use App\Models\Journey;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class BadgeService
{
    /**
     * BR-07: idempotent secara ganda — firstOrCreate di kode, unique index
     * (user_id, badge_id) di DB sebagai pengaman race condition (mis. event
     * JourneyCompleted fire dua kali hampir bersamaan).
     */
    public function awardForJourney(User $user, Journey $journey): ?UserBadge
    {
        $badge = Badge::query()->where('journey_id', $journey->id)->first();

        if ($badge === null) {
            return null;
        }

        try {
            return UserBadge::query()->firstOrCreate(
                ['user_id' => $user->id, 'badge_id' => $badge->id],
                ['earned_at' => now()],
            );
        } catch (UniqueConstraintViolationException) {
            return UserBadge::query()
                ->where('user_id', $user->id)
                ->where('badge_id', $badge->id)
                ->first();
        }
    }
}
