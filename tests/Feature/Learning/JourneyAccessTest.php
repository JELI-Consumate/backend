<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Module;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BR-01: journey ke-N hanya terbuka jika journey ke-(N-1) di sektor yang sama completed.
 * Journey pertama (order terkecil) selalu terbuka.
 */
final class JourneyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_journey_in_sector_is_always_unlocked(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}")->assertOk();
    }

    public function test_second_journey_is_locked_before_first_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $second = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$second->id}");

        $response->assertStatus(403)->assertJsonPath('code', 'JOURNEY_LOCKED');
    }

    public function test_second_journey_unlocks_after_first_completed(): void
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

        $this->actingAs($user)->getJson("/api/v1/journeys/{$second->id}")->assertOk();
    }

    public function test_locked_journey_does_not_leak_into_third_journey_unlock(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $third = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 3]);

        $this->actingAs($user)->getJson("/api/v1/journeys/{$third->id}")
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

        DB::enableQueryLog();
        $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}")->assertOk();

        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }
}
