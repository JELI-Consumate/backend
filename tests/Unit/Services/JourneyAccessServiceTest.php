<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Journey;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use App\Services\Learning\JourneyAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BR-01: journey tidak lagi sequential per-order. Journey manapun di sektor
 * terbuka asal user sudah menyelesaikan survei pretest sektor tersebut.
 */
final class JourneyAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_journey_is_locked_when_pretest_survey_not_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $this->assertFalse(app(JourneyAccessService::class)->isUnlocked($user, $journey));
    }

    public function test_journey_is_unlocked_when_pretest_survey_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        SectorProgress::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'pretest_survey_completed_at' => now(),
        ]);

        $this->assertTrue(app(JourneyAccessService::class)->isUnlocked($user, $journey));
    }

    public function test_later_journey_does_not_require_earlier_journey_to_be_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $second = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        SectorProgress::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'pretest_survey_completed_at' => now(),
        ]);

        $this->assertTrue(app(JourneyAccessService::class)->isUnlocked($user, $second));
    }

    public function test_completed_pretest_survey_in_different_sector_does_not_unlock(): void
    {
        $user = User::factory()->create();
        $sectorA = Sector::factory()->create();
        $sectorB = Sector::factory()->create();

        $journeyInB = Journey::factory()->create(['sector_id' => $sectorB->id, 'order' => 1]);

        SectorProgress::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sectorA->id,
            'pretest_survey_completed_at' => now(),
        ]);

        $this->assertFalse(app(JourneyAccessService::class)->isUnlocked($user, $journeyInB));
    }

    public function test_unlocked_map_for_sector_is_uniform_across_journeys(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $j1 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $j2 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $j3 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 3]);

        SectorProgress::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'pretest_survey_completed_at' => now(),
        ]);

        $map = app(JourneyAccessService::class)->unlockedMapForSector($user, $sector);

        $this->assertTrue($map[$j1->id]);
        $this->assertTrue($map[$j2->id]);
        $this->assertTrue($map[$j3->id]);
    }

    public function test_unlocked_map_matches_is_unlocked_result_per_journey(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $j1 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $j2 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        $service = app(JourneyAccessService::class);
        $map = $service->unlockedMapForSector($user, $sector);

        $this->assertSame($service->isUnlocked($user, $j1), $map[$j1->id]);
        $this->assertSame($service->isUnlocked($user, $j2), $map[$j2->id]);
    }
}
