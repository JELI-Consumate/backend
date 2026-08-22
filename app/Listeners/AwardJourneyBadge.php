<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\JourneyCompleted;
use App\Services\Gamification\BadgeService;

final readonly class AwardJourneyBadge
{
    public function __construct(private BadgeService $badges) {}

    public function handle(JourneyCompleted $event): void
    {
        $this->badges->awardForJourney($event->user, $event->journey);
    }
}
