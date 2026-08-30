<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Journeys\Pages\EditJourney;
use App\Filament\Resources\Journeys\RelationManagers\BadgeRelationManager;
use App\Models\Badge;
use App\Models\Journey;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tab "Badge" di halaman edit Journey (BadgeRelationManager) -- badge yang
 * otomatis diberikan ke user begitu journey ini selesai (lihat
 * App\Services\Gamification\BadgeService). Relasinya HasOne, jadi tab ini
 * cuma pernah berisi 0 atau 1 baris.
 */
final class BadgeRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Menampilkan tabel relation manager ini me-resolve icon_url tiap
        // baris lewat ImageColumn (disk default, "r2" di .env sungguhan) --
        // fake supaya test tidak benar-benar memanggil Cloudflare R2.
        Storage::fake(config('filament.default_filesystem_disk', 'public'));
    }

    private function makeJourney(): Journey
    {
        $sector = Sector::factory()->create();

        return Journey::factory()->create(['sector_id' => $sector->id]);
    }

    public function test_journey_edit_page_loads_with_badge_relation_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $journey = $this->makeJourney();

        $this->actingAs($admin)->get("/admin/journeys/{$journey->id}/edit")->assertOk();
    }

    public function test_creating_a_badge_attaches_it_to_the_journey(): void
    {
        $admin = User::factory()->admin()->create();
        $journey = $this->makeJourney();

        Livewire::actingAs($admin)
            ->test(BadgeRelationManager::class, ['ownerRecord' => $journey, 'pageClass' => EditJourney::class])
            ->callTableAction('create', data: [
                'name' => 'Consumer Rights Explorer (Penjelajah Hak Konsumen)',
                'description' => 'Deskripsi badge lengkap.',
                'congratulation_message' => 'Selamat! Kamu telah menuntaskan Journey 1.',
                'motivational_message' => 'Yuk lanjut ke Journey 2!',
                'icon_url' => UploadedFile::fake()->image('badge.png'),
            ])
            ->assertHasNoTableActionErrors();

        $badge = Badge::query()->where('journey_id', $journey->id)->firstOrFail();
        $this->assertSame('Consumer Rights Explorer (Penjelajah Hak Konsumen)', $badge->name);
        $this->assertSame('Yuk lanjut ke Journey 2!', $badge->motivational_message);
        $this->assertNotNull($badge->icon_url);
        Storage::disk(config('filament.default_filesystem_disk', 'public'))->assertExists($badge->icon_url);
    }

    public function test_required_fields_are_validated_when_creating_a_badge(): void
    {
        $admin = User::factory()->admin()->create();
        $journey = $this->makeJourney();

        Livewire::actingAs($admin)
            ->test(BadgeRelationManager::class, ['ownerRecord' => $journey, 'pageClass' => EditJourney::class])
            ->callTableAction('create', data: [])
            ->assertHasTableActionErrors(['name', 'description', 'congratulation_message', 'motivational_message', 'icon_url']);

        $this->assertSame(0, Badge::query()->where('journey_id', $journey->id)->count());
    }

    /**
     * `badges.journey_id` unique -- begitu journey sudah punya badge, tombol
     * "Buat" harus hilang supaya admin tidak coba bikin yang kedua.
     */
    public function test_create_action_is_hidden_once_the_journey_already_has_a_badge(): void
    {
        $admin = User::factory()->admin()->create();
        $journey = $this->makeJourney();
        Badge::factory()->create(['journey_id' => $journey->id]);

        Livewire::actingAs($admin)
            ->test(BadgeRelationManager::class, ['ownerRecord' => $journey, 'pageClass' => EditJourney::class])
            ->assertTableActionHidden('create');
    }

    public function test_editing_a_badge_updates_its_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $journey = $this->makeJourney();

        // FileUpload cuma menghidrasi state dari file yang benar-benar ada di
        // disk (Storage::fake() dari setUp) -- path acak dari factory tidak
        // akan ketemu, dan field wajibnya gagal validasi saat edit disubmit.
        $disk = config('filament.default_filesystem_disk', 'public');
        $existingIconPath = UploadedFile::fake()->image('existing.png')->store('badges', $disk);
        $badge = Badge::factory()->create([
            'journey_id' => $journey->id,
            'name' => 'Nama Lama',
            'icon_url' => $existingIconPath,
        ]);

        Livewire::actingAs($admin)
            ->test(BadgeRelationManager::class, ['ownerRecord' => $journey, 'pageClass' => EditJourney::class])
            // icon_url sengaja tidak di-override -- FileUpload sudah di-hidrasi
            // dari record saat action mount, dan set ulang lewat string mentah
            // (bukan UploadedFile sungguhan) bikin validasinya error tipe.
            ->callTableAction('edit', record: $badge, data: [
                'name' => 'Nama Baru',
                'description' => $badge->description,
                'congratulation_message' => $badge->congratulation_message ?? 'Selamat!',
                'motivational_message' => $badge->motivational_message ?? 'Semangat!',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Nama Baru', $badge->refresh()->name);
    }
}
