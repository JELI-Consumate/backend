<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\Sector;
use App\Models\User;
use App\Models\VideoContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProgressEngineTest extends TestCase
{
    use RefreshDatabase;

    private function createPage(Module $module, int $order): ModulePage
    {
        $video = VideoContent::factory()->create();

        return ModulePage::factory()->create([
            'module_id' => $module->id,
            'order' => $order,
            'contentable_type' => 'video',
            'contentable_id' => $video->id,
        ]);
    }

    /**
     * BR-11: complete berulang tidak mengubah completed_at yang sudah terisi.
     */
    public function test_completing_a_page_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id, 'estimated_minutes' => 10]);
        $page = $this->createPage($module, 1);

        $first = $this->actingAs($user)->postJson("/api/v1/module-pages/{$page->id}/complete")->assertOk();
        $completedAt = $first->json('data.completed_at');

        $second = $this->actingAs($user)->postJson("/api/v1/module-pages/{$page->id}/complete")->assertOk();

        $this->assertSame($completedAt, $second->json('data.completed_at'));
    }

    /**
     * BR-03: progres journey berbobot durasi — module hanya berkontribusi kalau
     * SELURUH halamannya completed, bukan kredit parsial per halaman.
     */
    public function test_journey_progress_is_weighted_by_duration_and_requires_full_module_completion(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $moduleA = Module::factory()->create(['journey_id' => $journey->id, 'estimated_minutes' => 10, 'is_required' => true]);
        $pageA1 = $this->createPage($moduleA, 1);
        $pageA2 = $this->createPage($moduleA, 2);

        $moduleB = Module::factory()->create(['journey_id' => $journey->id, 'estimated_minutes' => 5, 'is_required' => true]);
        $pageB1 = $this->createPage($moduleB, 1);

        // Baru 1 dari 2 halaman moduleA selesai -> moduleA belum berkontribusi sama sekali.
        $this->actingAs($user)->postJson("/api/v1/module-pages/{$pageA1->id}/complete")->assertOk();

        $response = $this->actingAs($user)->getJson("/api/v1/progress/journeys/{$journey->id}");
        $response->assertOk()->assertJsonPath('data.progress_percent', 0)->assertJsonPath('data.status', 'not_started');

        // ModuleA selesai penuh (10 dari total 15 menit) -> 66%.
        $this->actingAs($user)->postJson("/api/v1/module-pages/{$pageA2->id}/complete")->assertOk();

        $response = $this->actingAs($user)->getJson("/api/v1/progress/journeys/{$journey->id}");
        $response->assertOk()->assertJsonPath('data.progress_percent', 66)->assertJsonPath('data.status', 'in_progress');

        // ModuleB selesai juga -> 100%, completed.
        $this->actingAs($user)->postJson("/api/v1/module-pages/{$pageB1->id}/complete")->assertOk();

        $response = $this->actingAs($user)->getJson("/api/v1/progress/journeys/{$journey->id}");
        $response->assertOk()
            ->assertJsonPath('data.progress_percent', 100)
            ->assertJsonPath('data.status', 'completed');
        $this->assertNotNull($response->json('data.completed_at'));
    }

    /**
     * BR-14 & BR-15: progres sektor naik bertahap (rata-rata berbobot durasi journey)
     * begitu salah satu journey selesai, bukan hanya saat seluruh sektor selesai.
     */
    public function test_sector_progress_increases_incrementally_as_journeys_complete(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();

        $journey1 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module1 = Module::factory()->create(['journey_id' => $journey1->id, 'estimated_minutes' => 10]);
        $page1 = $this->createPage($module1, 1);

        $journey2 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $module2 = Module::factory()->create(['journey_id' => $journey2->id, 'estimated_minutes' => 10]);
        $page2 = $this->createPage($module2, 1);

        // Selesaikan journey1 sepenuhnya.
        $this->actingAs($user)->postJson("/api/v1/module-pages/{$page1->id}/complete")->assertOk();

        $response = $this->actingAs($user)->getJson("/api/v1/progress/sectors/{$sector->slug}");
        $response->assertOk()
            ->assertJsonPath('data.progress_percent', 50)
            ->assertJsonPath('data.status', 'in_progress');

        // journey2 baru unlocked setelah journey1 completed.
        $this->actingAs($user)->postJson("/api/v1/module-pages/{$page2->id}/complete")->assertOk();

        $response = $this->actingAs($user)->getJson("/api/v1/progress/sectors/{$sector->slug}");
        $response->assertOk()
            ->assertJsonPath('data.progress_percent', 100)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_cannot_complete_page_in_locked_journey(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $lockedJourney = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $module = Module::factory()->create(['journey_id' => $lockedJourney->id]);
        $page = $this->createPage($module, 1);

        $this->actingAs($user)->postJson("/api/v1/module-pages/{$page->id}/complete")
            ->assertStatus(403)->assertJsonPath('code', 'JOURNEY_LOCKED');
    }

    public function test_update_last_position_marks_page_in_progress(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = $this->createPage($module, 1);

        $response = $this->actingAs($user)->patchJson("/api/v1/module-pages/{$page->id}/position", ['position' => 42]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.last_position', 42);
    }

    public function test_position_update_rejects_negative_value(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = $this->createPage($module, 1);

        $this->actingAs($user)->patchJson("/api/v1/module-pages/{$page->id}/position", ['position' => -1])
            ->assertStatus(422);
    }

    public function test_progress_summary_lists_active_sectors(): void
    {
        $user = User::factory()->create();
        Sector::factory()->create(['is_active' => true]);
        Sector::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->getJson('/api/v1/progress/summary');

        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'not_started');
    }
}
