<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\Sector;
use App\Models\User;
use App\Services\Progress\ProgressResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProgressResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_the_first_incomplete_page_ordered_by_sector_journey_module_and_page(): void
    {
        $user = User::factory()->create();

        $sector = Sector::factory()->create(['order' => 1]);
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id, 'order' => 1]);
        $page1 = ModulePage::factory()->create(['module_id' => $module->id, 'order' => 1]);
        $page2 = ModulePage::factory()->create(['module_id' => $module->id, 'order' => 2]);

        // page1 sudah completed -> page2 yang harus jadi "next".
        ModuleProgress::factory()->completed()->create([
            'user_id' => $user->id,
            'module_page_id' => $page1->id,
        ]);

        $result = app(ProgressResolverService::class)->resolveNext($user);

        $this->assertSame($sector->id, $result['sector_id']);
        $this->assertSame($journey->id, $result['journey_id']);
        $this->assertSame($page2->id, $result['module_page_id']);
    }

    public function test_returns_nulls_when_every_page_is_completed(): void
    {
        $user = User::factory()->create();

        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create(['module_id' => $module->id]);

        ModuleProgress::factory()->completed()->create([
            'user_id' => $user->id,
            'module_page_id' => $page->id,
        ]);

        $result = app(ProgressResolverService::class)->resolveNext($user);

        $this->assertNull($result['sector_id']);
        $this->assertNull($result['journey_id']);
        $this->assertNull($result['module_page_id']);
    }

    public function test_ignores_pages_belonging_to_inactive_sectors(): void
    {
        $user = User::factory()->create();

        $inactiveSector = Sector::factory()->create(['is_active' => false, 'order' => 1]);
        $inactiveJourney = Journey::factory()->create(['sector_id' => $inactiveSector->id, 'order' => 1]);
        $inactiveModule = Module::factory()->create(['journey_id' => $inactiveJourney->id, 'order' => 1]);
        ModulePage::factory()->create(['module_id' => $inactiveModule->id, 'order' => 1]);

        $activeSector = Sector::factory()->create(['is_active' => true, 'order' => 2]);
        $activeJourney = Journey::factory()->create(['sector_id' => $activeSector->id, 'order' => 1]);
        $activeModule = Module::factory()->create(['journey_id' => $activeJourney->id, 'order' => 1]);
        $activePage = ModulePage::factory()->create(['module_id' => $activeModule->id, 'order' => 1]);

        $result = app(ProgressResolverService::class)->resolveNext($user);

        $this->assertSame($activePage->id, $result['module_page_id']);
    }

    public function test_does_not_treat_in_progress_page_as_completed(): void
    {
        $user = User::factory()->create();

        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create(['module_id' => $module->id]);

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $page->id,
            'status' => ProgressStatus::InProgress,
        ]);

        $result = app(ProgressResolverService::class)->resolveNext($user);

        $this->assertSame($page->id, $result['module_page_id']);
    }
}
