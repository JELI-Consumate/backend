<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\Sector;
use App\Models\User;
use App\Models\VideoContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module ke-N dalam satu journey hanya terbuka kalau module ke-(N-1) di
 * journey yang sama sudah completed SELURUH halamannya. Module pertama
 * (order terkecil) selalu terbuka. Lihat ModuleAccessService.
 */
final class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Module, 1: ModulePage}
     */
    private function createModuleWithPage(string $journeyId, int $order): array
    {
        $module = Module::factory()->create(['journey_id' => $journeyId, 'order' => $order]);
        $video = VideoContent::factory()->create();
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'video',
            'contentable_id' => $video->id,
        ]);

        return [$module, $page];
    }

    public function test_journey_detail_marks_only_first_module_unlocked_by_default(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        [$first] = $this->createModuleWithPage($journey->id, 1);
        [$second] = $this->createModuleWithPage($journey->id, 2);
        [$third] = $this->createModuleWithPage($journey->id, 3);

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}");

        $response->assertOk()
            ->assertJsonPath('data.modules.0.id', $first->id)
            ->assertJsonPath('data.modules.0.locked', false)
            ->assertJsonPath('data.modules.1.id', $second->id)
            ->assertJsonPath('data.modules.1.locked', true)
            ->assertJsonPath('data.modules.2.id', $third->id)
            ->assertJsonPath('data.modules.2.locked', true);
    }

    public function test_journey_detail_unlocks_next_module_once_previous_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        [, $firstPage] = $this->createModuleWithPage($journey->id, 1);
        $this->createModuleWithPage($journey->id, 2);
        $this->createModuleWithPage($journey->id, 3);

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $firstPage->id,
            'status' => ProgressStatus::Completed,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}");

        $response->assertOk()
            ->assertJsonPath('data.modules.0.locked', false)
            ->assertJsonPath('data.modules.1.locked', false)
            // Module kedua belum completed -> module ketiga tetap terkunci.
            ->assertJsonPath('data.modules.2.locked', true);
    }

    public function test_module_show_returns_403_when_previous_module_not_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $this->createModuleWithPage($journey->id, 1);
        [$second] = $this->createModuleWithPage($journey->id, 2);

        $this->actingAs($user)->getJson("/api/v1/modules/{$second->id}")
            ->assertStatus(403)->assertJsonPath('code', 'MODULE_LOCKED');
    }
}
