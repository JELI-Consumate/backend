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
use App\Services\Learning\ModuleAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module order terkecil di journey-nya selalu terbuka; module berikutnya
 * terbuka hanya kalau module sebelumnya (order - 1) sudah completed SELURUH
 * halamannya -- lihat ModuleAccessService.
 */
final class ModuleAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private function journey(): Journey
    {
        $sector = Sector::factory()->create();

        return Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
    }

    public function test_first_module_in_journey_is_always_unlocked(): void
    {
        $user = User::factory()->create();
        $module = Module::factory()->create(['journey_id' => $this->journey()->id, 'order' => 1]);

        $this->assertTrue(app(ModuleAccessService::class)->isUnlocked($user, $module));
    }

    public function test_second_module_is_locked_when_first_not_completed(): void
    {
        $user = User::factory()->create();
        $journey = $this->journey();
        $first = Module::factory()->create(['journey_id' => $journey->id, 'order' => 1]);
        ModulePage::factory()->create(['module_id' => $first->id]);
        $second = Module::factory()->create(['journey_id' => $journey->id, 'order' => 2]);

        $this->assertFalse(app(ModuleAccessService::class)->isUnlocked($user, $second));
    }

    public function test_second_module_is_unlocked_when_first_fully_completed(): void
    {
        $user = User::factory()->create();
        $journey = $this->journey();
        $first = Module::factory()->create(['journey_id' => $journey->id, 'order' => 1]);
        $page = ModulePage::factory()->create(['module_id' => $first->id]);
        $second = Module::factory()->create(['journey_id' => $journey->id, 'order' => 2]);

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $page->id,
            'status' => ProgressStatus::Completed,
        ]);

        $this->assertTrue(app(ModuleAccessService::class)->isUnlocked($user, $second));
    }

    public function test_second_module_stays_locked_when_only_some_pages_of_first_completed(): void
    {
        $user = User::factory()->create();
        $journey = $this->journey();
        $first = Module::factory()->create(['journey_id' => $journey->id, 'order' => 1]);
        $page1 = ModulePage::factory()->create(['module_id' => $first->id, 'order' => 1]);
        ModulePage::factory()->create(['module_id' => $first->id, 'order' => 2]);
        $second = Module::factory()->create(['journey_id' => $journey->id, 'order' => 2]);

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $page1->id,
            'status' => ProgressStatus::Completed,
        ]);

        $this->assertFalse(app(ModuleAccessService::class)->isUnlocked($user, $second));
    }

    public function test_completed_module_in_different_journey_does_not_unlock(): void
    {
        $user = User::factory()->create();
        $journeyA = $this->journey();
        $journeyB = $this->journey();

        $firstInA = Module::factory()->create(['journey_id' => $journeyA->id, 'order' => 1]);
        $pageInA = ModulePage::factory()->create(['module_id' => $firstInA->id]);
        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $pageInA->id,
            'status' => ProgressStatus::Completed,
        ]);

        $firstInB = Module::factory()->create(['journey_id' => $journeyB->id, 'order' => 1]);
        ModulePage::factory()->create(['module_id' => $firstInB->id]); // sengaja TIDAK completed
        $secondInB = Module::factory()->create(['journey_id' => $journeyB->id, 'order' => 2]);

        // Completed cuma di journey A, module pertama journey B belum completed
        // -> module kedua journey B tetap terkunci.
        $this->assertFalse(app(ModuleAccessService::class)->isUnlocked($user, $secondInB));
    }
}
