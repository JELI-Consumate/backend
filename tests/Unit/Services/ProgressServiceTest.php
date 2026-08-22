<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ProgressStatus;
use App\Events\JourneyCompleted;
use App\Events\JourneyProgressRecalculated;
use App\Events\ModuleCompleted;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\Sector;
use App\Models\User;
use App\Services\Progress\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_page_completed_creates_completed_progress_row(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $page = ModulePage::factory()->create();

        $progress = app(ProgressService::class)->markPageCompleted($user, $page);

        $this->assertSame(ProgressStatus::Completed, $progress->status);
        $this->assertNotNull($progress->completed_at);
        $this->assertDatabaseHas('module_progress', [
            'user_id' => $user->id,
            'module_page_id' => $page->id,
            'status' => ProgressStatus::Completed->value,
        ]);
    }

    /**
     * BR-11: idempotent — status/completed_at sudah completed tidak diubah lagi.
     */
    public function test_mark_page_completed_is_idempotent(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $page = ModulePage::factory()->create();

        $first = app(ProgressService::class)->markPageCompleted($user, $page);
        $completedAt = $first->completed_at;

        $second = app(ProgressService::class)->markPageCompleted($user, $page);

        $this->assertSame($first->id, $second->id);
        $this->assertTrue($completedAt->equalTo($second->completed_at));
    }

    public function test_mark_page_completed_dispatches_module_completed_event(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $page = ModulePage::factory()->create();

        app(ProgressService::class)->markPageCompleted($user, $page);

        Event::assertDispatched(ModuleCompleted::class, fn (ModuleCompleted $event): bool => $event->user->id === $user->id && $event->page->id === $page->id);
    }

    public function test_update_last_position_sets_in_progress_when_not_started(): void
    {
        $user = User::factory()->create();
        $page = ModulePage::factory()->create();

        $progress = app(ProgressService::class)->updateLastPosition($user, $page, 42);

        $this->assertSame(ProgressStatus::InProgress, $progress->status);
        $this->assertSame(42, $progress->last_position);
    }

    public function test_update_last_position_does_not_downgrade_completed_status(): void
    {
        $user = User::factory()->create();
        $page = ModulePage::factory()->create();
        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $page->id,
            'status' => ProgressStatus::Completed,
        ]);

        $progress = app(ProgressService::class)->updateLastPosition($user, $page, 99);

        $this->assertSame(ProgressStatus::Completed, $progress->status);
        $this->assertSame(99, $progress->last_position);
    }

    /**
     * BR-03: persen = jumlah menit module wajib yang seluruh halamannya completed,
     * dibagi total menit module wajib.
     */
    public function test_recalculate_journey_computes_percent_from_required_modules_only(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);

        $requiredModule = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => true, 'estimated_minutes' => 10]);
        $requiredPage = ModulePage::factory()->create(['module_id' => $requiredModule->id]);

        $optionalModule = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => false, 'estimated_minutes' => 90]);
        ModulePage::factory()->create(['module_id' => $optionalModule->id]);

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $requiredPage->id,
            'status' => ProgressStatus::Completed,
        ]);

        $progress = app(ProgressService::class)->recalculateJourney($user, $journey);

        // Required module (10 menit) selesai semua halamannya, optional (90 menit) diabaikan -> 100%.
        $this->assertSame(100, $progress->progress_percent);
        $this->assertSame(ProgressStatus::Completed, $progress->status);
        $this->assertNotNull($progress->completed_at);
    }

    public function test_recalculate_journey_is_in_progress_when_partially_completed(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);

        $moduleDone = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => true, 'estimated_minutes' => 30]);
        $pageDone = ModulePage::factory()->create(['module_id' => $moduleDone->id]);

        // Module belum disentuh sama sekali (punya page tapi tanpa progress) —
        // kalau module ini TIDAK diberi page, whereDoesntHave('pages', ...) di
        // service jadi trivially true (dianggap "selesai") walau belum disentuh.
        $moduleNotDone = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => true, 'estimated_minutes' => 70]);
        ModulePage::factory()->create(['module_id' => $moduleNotDone->id]);

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $pageDone->id,
            'status' => ProgressStatus::Completed,
        ]);

        $progress = app(ProgressService::class)->recalculateJourney($user, $journey);

        $this->assertSame(30, $progress->progress_percent);
        $this->assertSame(ProgressStatus::InProgress, $progress->status);
    }

    public function test_recalculate_journey_always_dispatches_recalculated_event_but_completed_event_only_on_transition(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);
        $module = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => true, 'estimated_minutes' => 10]);
        ModulePage::factory()->create(['module_id' => $module->id]);

        app(ProgressService::class)->recalculateJourney($user, $journey);

        Event::assertDispatched(JourneyProgressRecalculated::class);
        Event::assertNotDispatched(JourneyCompleted::class);
    }

    public function test_recalculate_journey_dispatches_completed_event_only_when_status_becomes_completed(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);
        $module = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => true, 'estimated_minutes' => 10]);
        $page = ModulePage::factory()->create(['module_id' => $module->id]);

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $page->id,
            'status' => ProgressStatus::Completed,
        ]);

        app(ProgressService::class)->recalculateJourney($user, $journey);

        Event::assertDispatched(JourneyCompleted::class);
    }

    /**
     * BR-14: rata-rata berbobot progress_percent tiap journey berdasar estimated_minutes.
     */
    public function test_recalculate_sector_computes_weighted_average_of_journeys(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();

        $journeyA = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1, 'estimated_minutes' => 60]);
        $journeyB = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2, 'estimated_minutes' => 40]);

        JourneyProgress::factory()->create(['user_id' => $user->id, 'journey_id' => $journeyA->id, 'status' => ProgressStatus::Completed, 'progress_percent' => 100]);
        JourneyProgress::factory()->create(['user_id' => $user->id, 'journey_id' => $journeyB->id, 'status' => ProgressStatus::InProgress, 'progress_percent' => 50]);

        $progress = app(ProgressService::class)->recalculateSector($user, $sector);

        // (60*100 + 40*50) / 100 = 80
        $this->assertSame(80, $progress->progress_percent);
        $this->assertSame(ProgressStatus::InProgress, $progress->status);
    }

    public function test_recalculate_sector_is_completed_only_when_all_journeys_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();

        $journeyA = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1, 'estimated_minutes' => 50]);
        $journeyB = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2, 'estimated_minutes' => 50]);

        JourneyProgress::factory()->create(['user_id' => $user->id, 'journey_id' => $journeyA->id, 'status' => ProgressStatus::Completed, 'progress_percent' => 100]);
        JourneyProgress::factory()->create(['user_id' => $user->id, 'journey_id' => $journeyB->id, 'status' => ProgressStatus::Completed, 'progress_percent' => 100]);

        $progress = app(ProgressService::class)->recalculateSector($user, $sector);

        $this->assertSame(ProgressStatus::Completed, $progress->status);
        $this->assertSame(100, $progress->progress_percent);
        $this->assertNotNull($progress->completed_at);
    }

    public function test_recalculate_sector_returns_not_started_when_no_journey_progress_exists(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1, 'estimated_minutes' => 50]);

        $progress = app(ProgressService::class)->recalculateSector($user, $sector);

        $this->assertSame(ProgressStatus::NotStarted, $progress->status);
        $this->assertSame(0, $progress->progress_percent);
    }
}
