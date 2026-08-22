<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Sector;
use App\Models\User;
use App\Services\Learning\JourneyAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BR-01: journey order terkecil di sektornya selalu terbuka; journey berikutnya
 * terbuka hanya kalau journey sebelumnya (order - 1) sudah completed.
 */
final class JourneyAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_journey_in_sector_is_always_unlocked(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $this->assertTrue(app(JourneyAccessService::class)->isUnlocked($user, $journey));
    }

    public function test_second_journey_is_locked_when_first_not_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $second = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        $this->assertFalse(app(JourneyAccessService::class)->isUnlocked($user, $second));
    }

    public function test_second_journey_is_unlocked_when_first_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $first = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $second = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        JourneyProgress::factory()->create([
            'user_id' => $user->id,
            'journey_id' => $first->id,
            'status' => ProgressStatus::Completed,
        ]);

        $this->assertTrue(app(JourneyAccessService::class)->isUnlocked($user, $second));
    }

    public function test_second_journey_stays_locked_when_first_only_in_progress(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $first = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $second = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        JourneyProgress::factory()->create([
            'user_id' => $user->id,
            'journey_id' => $first->id,
            'status' => ProgressStatus::InProgress,
        ]);

        $this->assertFalse(app(JourneyAccessService::class)->isUnlocked($user, $second));
    }

    public function test_completed_progress_in_different_sector_does_not_unlock(): void
    {
        $user = User::factory()->create();
        $sectorA = Sector::factory()->create();
        $sectorB = Sector::factory()->create();

        $firstInA = Journey::factory()->create(['sector_id' => $sectorA->id, 'order' => 1]);
        Journey::factory()->create(['sector_id' => $sectorB->id, 'order' => 1]);
        $secondInB = Journey::factory()->create(['sector_id' => $sectorB->id, 'order' => 2]);

        JourneyProgress::factory()->create([
            'user_id' => $user->id,
            'journey_id' => $firstInA->id,
            'status' => ProgressStatus::Completed,
        ]);

        // Progress completed cuma di sektor A, journey pertama sektor B belum
        // completed -> journey kedua sektor B tetap terkunci.
        $this->assertFalse(app(JourneyAccessService::class)->isUnlocked($user, $secondInB));
    }

    public function test_unlocked_map_for_sector_chains_lock_status_across_journeys(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $j1 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $j2 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $j3 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 3]);

        JourneyProgress::factory()->create([
            'user_id' => $user->id,
            'journey_id' => $j1->id,
            'status' => ProgressStatus::Completed,
        ]);

        $map = app(JourneyAccessService::class)->unlockedMapForSector($user, $sector);

        $this->assertTrue($map[$j1->id]);
        $this->assertTrue($map[$j2->id]);
        $this->assertFalse($map[$j3->id]);
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
