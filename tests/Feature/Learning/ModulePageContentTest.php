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

final class ModulePageContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_page_show_returns_single_resolved_page(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $video = VideoContent::factory()->create(['title' => 'Video Uji', 'description' => 'Deskripsi video uji.']);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'video',
            'contentable_id' => $video->id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");

        $response->assertOk()
            ->assertJsonPath('data.content_type', 'video')
            ->assertJsonPath('data.content.title', 'Video Uji')
            ->assertJsonPath('data.content.description', 'Deskripsi video uji.')
            ->assertJsonPath('data.progress.status', 'not_started');
    }

    public function test_module_page_show_returns_404_for_unknown_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/module-pages/999999')->assertNotFound();
    }

    public function test_module_page_show_returns_403_when_journey_locked(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $lockedJourney = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $module = Module::factory()->create(['journey_id' => $lockedJourney->id]);
        $video = VideoContent::factory()->create();
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'video',
            'contentable_id' => $video->id,
        ]);

        $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}")
            ->assertStatus(403)->assertJsonPath('code', 'JOURNEY_LOCKED');
    }
}
