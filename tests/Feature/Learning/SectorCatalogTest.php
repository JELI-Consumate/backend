<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Journey;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SectorCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sectors_index_lists_active_sectors_with_progress(): void
    {
        $user = User::factory()->create();
        Sector::factory()->create(['is_active' => true, 'order' => 1]);
        Sector::factory()->create(['is_active' => false, 'order' => 2]);

        $response = $this->actingAs($user)->getJson('/api/v1/sectors');

        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.progress.status', 'not_started');
        $response->assertJsonPath('data.0.progress.percent', 0);
    }

    /**
     * Filament's FileUpload menyimpan path relatif ("sectors/icons/x.png"),
     * bukan URL absolut -- resource harus mengubahnya jadi URL yang benar-benar
     * bisa dimuat client (regresi: sebelumnya path mentah dikirim apa adanya
     * dan gambar gagal tampil di app). Disk di-force ke "public" di sini
     * supaya deterministik terlepas dari FILAMENT_FILESYSTEM_DISK sungguhan
     * di .env mesin yang menjalankan test (lihat MediaUrlTest untuk cakupan
     * disk cloud/r2-nya).
     */
    public function test_sector_icon_url_is_resolved_to_an_absolute_url(): void
    {
        config(['filament.default_filesystem_disk' => 'public']);

        $user = User::factory()->create();
        Sector::factory()->create([
            'is_active' => true,
            'icon_url' => 'sectors/icons/e-commerce.png',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/sectors');

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '#^https?://[^/]+/storage/sectors/icons/e-commerce\.png$#',
            $response->json('data.0.icon_url'),
        );
    }

    /**
     * Di production disk default-nya "r2" (Cloudflare R2, object storage
     * S3-compatible) -- URL yang dihasilkan harus ikut domain publik disk
     * itu, bukan diasumsikan selalu lokal lewat /storage/. R2_PUBLIC_URL
     * palsu di sini, bukan kredensial sungguhan -- Storage::url() cuma
     * menyusun string, tidak pernah benar-benar menghubungi R2.
     */
    public function test_sector_icon_url_uses_the_cloud_disks_public_url_when_configured(): void
    {
        config([
            'filament.default_filesystem_disk' => 'r2',
            'filesystems.disks.r2.url' => 'https://media.example-cdn.test',
        ]);

        $user = User::factory()->create();
        Sector::factory()->create([
            'is_active' => true,
            'icon_url' => 'sectors/icons/e-commerce.png',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/sectors');

        $response->assertOk()->assertJsonPath(
            'data.0.icon_url',
            'https://media.example-cdn.test/sectors/icons/e-commerce.png',
        );
    }

    public function test_sector_show_returns_journeys_with_lock_status(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['is_active' => true]);
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        $response = $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}");

        $response->assertOk()
            ->assertJsonPath('data.journeys.0.is_unlocked', true)
            ->assertJsonPath('data.journeys.1.is_unlocked', false);
    }

    public function test_sector_show_returns_404_for_unknown_slug(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/sectors/tidak-ada')->assertNotFound();
    }

    public function test_sector_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/sectors')->assertUnauthorized();
    }

    /**
     * Budget query GET /sectors (06-nonfunctional-ops.md §8, target ≤8 query).
     */
    public function test_sectors_index_stays_within_query_budget(): void
    {
        $user = User::factory()->create();
        Sector::factory()->count(5)->sequence(fn ($sequence) => ['order' => $sequence->index + 1])->create(['is_active' => true]);

        DB::enableQueryLog();
        $this->actingAs($user)->getJson('/api/v1/sectors')->assertOk();

        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }

    /**
     * Budget query GET /sectors/{slug} (06-nonfunctional-ops.md §8, target ≤8 query).
     */
    public function test_sector_show_stays_within_query_budget(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['is_active' => true]);
        Journey::factory()->count(4)->sequence(fn ($sequence) => ['order' => $sequence->index + 1])->create(['sector_id' => $sector->id]);

        DB::enableQueryLog();
        $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}")->assertOk();

        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }
}
