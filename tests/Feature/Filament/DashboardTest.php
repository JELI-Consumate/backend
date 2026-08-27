<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\DashboardOverviewWidget;
use App\Models\Journey;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman awal panel admin (Dashboard) — sapaan personal + ringkasan
 * struktur konten, tanpa AccountWidget/FilamentInfoWidget bawaan Filament.
 */
final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_with_personalized_heading(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Budi Admin']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Selamat datang, Budi Admin');
        $response->assertSee('Super Admin');
    }

    public function test_dashboard_shows_sector_admin_subheading(): void
    {
        $sector = Sector::factory()->create(['name' => 'Sector Uji']);
        $admin = User::factory()->sectorAdmin($sector)->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Admin Sector');
        $response->assertSee('Sector Uji');
    }

    public function test_dashboard_does_not_render_filament_info_widget(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Filament v', escape: false);
    }

    public function test_dashboard_overview_stats_are_scoped_to_admin_sector(): void
    {
        $ownSector = Sector::factory()->create();
        $otherSector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $ownSector->id]);
        Journey::factory()->count(2)->create(['sector_id' => $otherSector->id]);

        $admin = User::factory()->sectorAdmin($ownSector)->create();

        $widget = new DashboardOverviewWidget;
        $this->actingAs($admin);

        $method = new \ReflectionMethod($widget, 'getStats');
        $stats = collect($method->invoke($widget))->keyBy(fn ($stat) => $stat->getLabel());

        $this->assertSame('1', $stats['Journey']->getValue());
        $this->assertSame('1', $stats['Sector']->getValue());
    }
}
