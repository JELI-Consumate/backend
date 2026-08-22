<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AnalyticsAndUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_view_page_loads_with_progress_relation_managers(): void
    {
        $admin = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $target = User::factory()->create();
        SectorProgress::factory()->create(['user_id' => $target->id, 'sector_id' => $sector->id]);
        JourneyProgress::factory()->create(['user_id' => $target->id, 'journey_id' => $journey->id, 'status' => ProgressStatus::Completed]);

        $this->actingAs($admin)->get("/admin/users/{$target->id}")->assertOk();
    }

    public function test_learning_analytics_page_loads_and_computes_journey_completion(): void
    {
        $admin = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $target = User::factory()->create();
        JourneyProgress::factory()->create(['user_id' => $target->id, 'journey_id' => $journey->id, 'status' => ProgressStatus::Completed]);

        $response = $this->actingAs($admin)->get('/admin/learning-analytics');

        $response->assertOk();
        $response->assertSee($journey->title);
    }
}
