<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Journey;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SectorCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sectors_index_lists_active_sectors_with_progress(): void
    {
        $user = User::factory()->create();
        Sector::factory()->create(['is_active' => true, 'order' => 1]);
        Sector::factory()->create(['is_active' => false, 'order' => 2]);

        $response = $this->actingAs($user)->getJson('/api/v1/sectors');

        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.progress.status', 'not_started');
        $response->assertJsonPath('data.0.progress.percent', 0);
    }

    public function test_sector_show_returns_journeys_with_lock_status(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['is_active' => true]);
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        $response = $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}");

        $response->assertOk()
            ->assertJsonPath('data.journeys.0.is_unlocked', true)
            ->assertJsonPath('data.journeys.1.is_unlocked', false);
    }

    public function test_sector_show_returns_404_for_unknown_slug(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/sectors/tidak-ada')->assertNotFound();
    }

    public function test_sector_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/sectors')->assertUnauthorized();
    }

    /**
     * Budget query GET /sectors (06-nonfunctional-ops.md §8, target ≤8 query).
     */
    public function test_sectors_index_stays_within_query_budget(): void
    {
        $user = User::factory()->create();
        Sector::factory()->count(5)->sequence(fn ($sequence) => ['order' => $sequence->index + 1])->create(['is_active' => true]);

        DB::enableQueryLog();
        $this->actingAs($user)->getJson('/api/v1/sectors')->assertOk();

        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }

    /**
     * Budget query GET /sectors/{slug} (06-nonfunctional-ops.md §8, target ≤8 query).
     */
    public function test_sector_show_stays_within_query_budget(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['is_active' => true]);
        Journey::factory()->count(4)->sequence(fn ($sequence) => ['order' => $sequence->index + 1])->create(['sector_id' => $sector->id]);

        DB::enableQueryLog();
        $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}")->assertOk();

        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }
}
