<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Journey;
use App\Models\Module;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\HasCompletedPretestSurvey;
use Tests\TestCase;

/**
 * BR-01: journey tidak lagi sequential per-order. Journey manapun di sektor
 * terbuka asal user sudah menyelesaikan survei pretest sektor tersebut.
 */
final class JourneyAccessTest extends TestCase
{
    use HasCompletedPretestSurvey, RefreshDatabase;

    public function test_journey_is_locked_when_pretest_survey_not_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}");

        $response->assertStatus(403)->assertJsonPath('code', 'JOURNEY_LOCKED');
    }

    public function test_journey_is_unlocked_once_pretest_survey_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $this->completePretestSurvey($user, $sector);

        $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}")->assertOk();
    }

    public function test_later_journey_does_not_require_earlier_journey_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $second = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $this->completePretestSurvey($user, $sector);

        $this->actingAs($user)->getJson("/api/v1/journeys/{$second->id}")->assertOk();
    }

    public function test_completed_pretest_survey_in_different_sector_does_not_unlock(): void
    {
        $user = User::factory()->create();
        $sectorA = Sector::factory()->create();
        $sectorB = Sector::factory()->create();
        $journeyInB = Journey::factory()->create(['sector_id' => $sectorB->id, 'order' => 1]);
        $this->completePretestSurvey($user, $sectorA);

        $this->actingAs($user)->getJson("/api/v1/journeys/{$journeyInB->id}")
            ->assertStatus(403)->assertJsonPath('code', 'JOURNEY_LOCKED');
    }

    /**
     * Budget query GET /journeys/{id} (06-nonfunctional-ops.md §8, target ≤8 query).
     */
    public function test_journey_show_stays_within_query_budget(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        Module::factory()->count(5)->sequence(fn ($sequence) => ['order' => $sequence->index + 1])->create(['journey_id' => $journey->id]);
        $this->completePretestSurvey($user, $sector);

        DB::enableQueryLog();
        $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}")->assertOk();

        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }
}
