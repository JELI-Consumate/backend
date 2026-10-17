<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ProgressStatus;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AnalyticsAndUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_view_page_loads_with_progress_relation_managers(): void
    {
        $admin = User::factory()->admin()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $target = User::factory()->create();
        SectorProgress::factory()->create(['user_id' => $target->id, 'sector_id' => $sector->id]);
        JourneyProgress::factory()->create(['user_id' => $target->id, 'journey_id' => $journey->id, 'status' => ProgressStatus::Completed]);

        $this->actingAs($admin)->get("/admin/users/{$target->id}")->assertOk();
    }

    public function test_learning_analytics_page_loads_and_computes_journey_completion(): void
    {
        $admin = User::factory()->admin()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $target = User::factory()->create();
        JourneyProgress::factory()->create(['user_id' => $target->id, 'journey_id' => $journey->id, 'status' => ProgressStatus::Completed]);

        $response = $this->actingAs($admin)->get('/admin/learning-analytics');

        $response->assertOk();
        $response->assertSee($journey->title);
    }

    public function test_sector_admin_filters_users_by_progress_interval_in_own_sector(): void
    {
        $sector = Sector::factory()->create();
        $other = Sector::factory()->create();
        $admin = User::factory()->sectorAdmin($sector)->create();

        $low = User::factory()->create();
        $high = User::factory()->create();
        SectorProgress::factory()->create(['user_id' => $low->id, 'sector_id' => $sector->id, 'progress_percent' => 25]);
        SectorProgress::factory()->create(['user_id' => $high->id, 'sector_id' => $sector->id, 'progress_percent' => 26]);
        SectorProgress::factory()->create(['user_id' => $high->id, 'sector_id' => $other->id, 'progress_percent' => 10]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->filterTable('sector_progress', ['ranges' => ['0-25']])
            ->assertCanSeeTableRecords([$low])
            ->assertCanNotSeeTableRecords([$high]);

        Livewire::test(ListUsers::class)
            ->filterTable('sector_progress', ['ranges' => ['0-25', '26-50']])
            ->assertCanSeeTableRecords([$low, $high]);
    }

    public function test_super_admin_needs_sector_for_progress_filter_to_apply(): void
    {
        $sector = Sector::factory()->create();
        $super = User::factory()->admin()->create();
        $user = User::factory()->create();
        SectorProgress::factory()->create(['user_id' => $user->id, 'sector_id' => $sector->id, 'progress_percent' => 80]);

        $this->actingAs($super);

        Livewire::test(ListUsers::class)
            ->filterTable('sector_progress', ['ranges' => ['0-25']])
            ->assertCanSeeTableRecords([$user]);

        Livewire::test(ListUsers::class)
            ->filterTable('sector_progress', ['sector_id' => $sector->id, 'ranges' => ['0-25']])
            ->assertCanNotSeeTableRecords([$user]);
    }
}
