<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Journeys\Pages\CreateJourney;
use App\Models\Journey;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Role admin panel: super_admin akses semua sector, admin dibatasi ke satu
 * sector (users.sector_id). Lihat App\Filament\Support\AdminScope.
 */
final class SectorAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel(filament()->getDefaultPanel()));
    }

    public function test_sector_admin_and_super_admin_can_access_panel(): void
    {
        $sector = Sector::factory()->create();

        $sectorAdmin = User::factory()->sectorAdmin($sector)->create();
        $superAdmin = User::factory()->admin()->create();

        $panel = filament()->getDefaultPanel();

        $this->assertTrue($sectorAdmin->canAccessPanel($panel));
        $this->assertTrue($superAdmin->canAccessPanel($panel));
    }

    public function test_sector_admin_only_sees_their_own_sector(): void
    {
        $ownSector = Sector::factory()->create(['name' => 'Sector Sendiri']);
        $otherSector = Sector::factory()->create(['name' => 'Sector Lain']);
        $admin = User::factory()->sectorAdmin($ownSector)->create();

        $response = $this->actingAs($admin)->get('/admin/sectors');

        $response->assertOk();
        $response->assertSee('Sector Sendiri');
        $response->assertDontSee('Sector Lain');
    }

    public function test_sector_admin_cannot_open_another_sectors_journey(): void
    {
        $ownSector = Sector::factory()->create();
        $otherSector = Sector::factory()->create();
        $otherJourney = Journey::factory()->create(['sector_id' => $otherSector->id]);
        $admin = User::factory()->sectorAdmin($ownSector)->create();

        $this->actingAs($admin)->get("/admin/journeys/{$otherJourney->id}/edit")->assertNotFound();
    }

    public function test_sector_admin_cannot_create_a_new_sector(): void
    {
        $sector = Sector::factory()->create();
        $admin = User::factory()->sectorAdmin($sector)->create();

        $this->actingAs($admin)->get('/admin/sectors/create')->assertForbidden();
    }

    public function test_only_super_admin_can_access_admin_management(): void
    {
        $sector = Sector::factory()->create();
        $sectorAdmin = User::factory()->sectorAdmin($sector)->create();
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($sectorAdmin)->get('/admin/admins')->assertForbidden();
        $this->actingAs($superAdmin)->get('/admin/admins')->assertOk();
    }

    /**
     * Lapis pertahanan kedua di AdminResource::getEloquentQuery() — halaman
     * list-nya sendiri sudah 403 lewat canViewAny(), tapi query-nya juga
     * sengaja dikosongkan total untuk non-super-admin (lihat komentar di
     * AdminResource).
     */
    public function test_admin_resource_query_is_empty_for_non_super_admin(): void
    {
        $sectorA = Sector::factory()->create();
        $sectorB = Sector::factory()->create();
        $admin = User::factory()->sectorAdmin($sectorA)->create(['name' => 'Admin A']);
        User::factory()->sectorAdmin($sectorB)->create(['name' => 'Admin B']);

        $this->actingAs($admin);

        $this->assertSame(0, AdminResource::getEloquentQuery()->count());
    }

    /**
     * Select "sector_id" di JourneyForm di-disable + di-default-kan ke
     * sector admin saat dia dibatasi — pastikan kombinasi disabled+default
     * itu tetap ikut ter-submit (bukan malah null/kosong).
     */
    public function test_sector_admin_creating_journey_auto_locks_sector_id(): void
    {
        $sector = Sector::factory()->create();
        $admin = User::factory()->sectorAdmin($sector)->create();

        Livewire::actingAs($admin)
            ->test(CreateJourney::class)
            ->fillForm([
                'title' => 'Journey Baru',
                'slug' => 'journey-baru',
                'order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $journey = Journey::withoutGlobalScopes()->where('slug', 'journey-baru')->firstOrFail();
        $this->assertSame($sector->id, $journey->sector_id);
    }

    public function test_creating_admin_via_panel_persists_role_and_sector(): void
    {
        $sector = Sector::factory()->create();
        $superAdmin = User::factory()->admin()->create();

        Livewire::actingAs($superAdmin)
            ->test(CreateAdmin::class)
            ->fillForm([
                'name' => 'Admin Baru',
                'email' => 'admin.baru@example.com',
                'password' => 'password123',
                'role' => UserRole::Admin->value,
                'sector_id' => $sector->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'admin.baru@example.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $created->role);
        $this->assertSame($sector->id, $created->sector_id);
        $this->assertTrue($created->canAccessPanel(filament()->getDefaultPanel()));
        $this->assertNotNull($created->email_verified_at);
    }
}
