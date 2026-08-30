<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProgressNextTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_next_incomplete_page(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create(['module_id' => $module->id, 'order' => 1]);

        $response = $this->actingAs($user)->getJson('/api/v1/progress/next');

        $response->assertOk()
            ->assertJsonPath('data.sector_id', $sector->id)
            ->assertJsonPath('data.journey_id', $journey->id)
            ->assertJsonPath('data.module_page_id', $page->id);
    }

    /**
     * §3.1 feature-notification.md: resolusi tujuan navigasi WAJIB real-time
     * saat endpoint ini dipanggil, bukan pakai data basi.
     */
    public function test_reflects_progress_completed_after_page_was_marked_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page1 = ModulePage::factory()->create(['module_id' => $module->id, 'order' => 1]);
        $page2 = ModulePage::factory()->create(['module_id' => $module->id, 'order' => 2]);

        $this->actingAs($user)->postJson("/api/v1/module-pages/{$page1->id}/complete")->assertOk();

        $response = $this->actingAs($user)->getJson('/api/v1/progress/next');

        $response->assertOk()->assertJsonPath('data.module_page_id', $page2->id);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/progress/next')->assertStatus(401);
    }
}
