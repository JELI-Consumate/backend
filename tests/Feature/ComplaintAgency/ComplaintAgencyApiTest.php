<?php

declare(strict_types=1);

namespace Tests\Feature\ComplaintAgency;

use App\Enums\ComplaintChannelType;
use App\Models\ComplaintAgency;
use App\Models\ComplaintAgencyChannel;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ComplaintAgencyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoints_require_login(): void
    {
        $this->getJson('/api/v1/complaint-agencies')->assertUnauthorized();
        $this->getJson('/api/v1/complaint-agencies/bpom')->assertUnauthorized();
        $this->getJson('/api/v1/complaint-categories')->assertUnauthorized();
    }

    public function test_index_lists_only_active_agencies_ordered(): void
    {
        ComplaintAgency::factory()->create(['short_name' => 'KEDUA', 'order' => 2]);
        ComplaintAgency::factory()->create(['short_name' => 'PERTAMA', 'order' => 1]);
        ComplaintAgency::factory()->create(['short_name' => 'MATI', 'is_active' => false]);
        ComplaintAgency::factory()->create(['short_name' => 'DIHAPUS', 'order' => 3])->delete();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/v1/complaint-agencies');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.short_name', 'PERTAMA')
            ->assertJsonPath('data.1.short_name', 'KEDUA')
            ->assertJsonStructure(['data' => [['id', 'slug', 'short_name', 'full_name', 'summary', 'logo_url', 'order']]]);
    }

    public function test_show_returns_detail_with_ordered_channels_and_computed_urls(): void
    {
        $agency = ComplaintAgency::factory()->create([
            'slug' => 'bpom',
            'when_to_contact' => ['Produk ilegal'],
        ]);
        ComplaintAgencyChannel::factory()->create([
            'complaint_agency_id' => $agency->id,
            'type' => ComplaintChannelType::Address,
            'label' => 'Alamat',
            'value' => 'Jl. Percobaan 1',
            'order' => 2,
        ]);
        ComplaintAgencyChannel::factory()->create([
            'complaint_agency_id' => $agency->id,
            'type' => ComplaintChannelType::Whatsapp,
            'label' => 'WhatsApp Halo BPOM',
            'value' => '0811-9181-533',
            'button_label' => 'Hubungi Halo BPOM',
            'order' => 1,
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/v1/complaint-agencies/bpom');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'bpom')
            ->assertJsonPath('data.when_to_contact', ['Produk ilegal'])
            ->assertJsonPath('data.channels.0.type', 'whatsapp')
            ->assertJsonPath('data.channels.0.url', 'https://wa.me/628119181533')
            ->assertJsonPath('data.channels.0.button_label', 'Hubungi Halo BPOM')
            ->assertJsonPath('data.channels.1.type', 'address')
            ->assertJsonPath('data.channels.1.url', null);
    }

    public function test_show_returns_404_for_inactive_deleted_or_unknown_slug(): void
    {
        ComplaintAgency::factory()->create(['slug' => 'mati', 'is_active' => false]);
        ComplaintAgency::factory()->create(['slug' => 'dihapus'])->delete();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/complaint-agencies/mati')->assertNotFound();
        $this->actingAs($user)->getJson('/api/v1/complaint-agencies/dihapus')->assertNotFound();
        $this->actingAs($user)->getJson('/api/v1/complaint-agencies/tidak-ada')->assertNotFound();
    }

    public function test_logo_url_is_resolved_to_an_absolute_url(): void
    {
        config(['filament.default_filesystem_disk' => 'public']);
        ComplaintAgency::factory()->create(['logo_url' => 'complaint-agencies/logos/bpom.png']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/v1/complaint-agencies');

        $this->assertMatchesRegularExpression(
            '#^https?://[^/]+/storage/complaint-agencies/logos/bpom\.png$#',
            $response->json('data.0.logo_url'),
        );
    }

    public function test_categories_embed_only_active_agencies_in_pivot_order(): void
    {
        $first = ComplaintAgency::factory()->create(['slug' => 'a', 'short_name' => 'A']);
        $second = ComplaintAgency::factory()->create(['slug' => 'b', 'short_name' => 'B']);
        $inactive = ComplaintAgency::factory()->create(['is_active' => false]);
        $category = ComplaintCategory::factory()->create(['problem' => 'Masalah X']);
        $category->agencies()->attach([
            $second->id => ['order' => 2],
            $first->id => ['order' => 1],
            $inactive->id => ['order' => 3],
        ]);
        ComplaintCategory::factory()->create(['is_active' => false]);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/v1/complaint-categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.problem', 'Masalah X')
            ->assertJsonCount(2, 'data.0.agencies')
            ->assertJsonPath('data.0.agencies.0.slug', 'a')
            ->assertJsonPath('data.0.agencies.1.slug', 'b');
    }

    public function test_query_count_does_not_grow_with_number_of_agencies(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $count = function (int $agencies): int {
            ComplaintAgency::query()->forceDelete();
            ComplaintCategory::query()->forceDelete();
            $category = ComplaintCategory::factory()->create();
            ComplaintAgency::factory()->count($agencies)->create()->each(function (ComplaintAgency $agency) use ($category): void {
                ComplaintAgencyChannel::factory()->count(2)->create(['complaint_agency_id' => $agency->id]);
                $category->agencies()->attach($agency->id, ['order' => 1]);
            });

            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->getJson('/api/v1/complaint-categories')->assertOk();
            $this->getJson('/api/v1/complaint-agencies')->assertOk();
            $this->getJson('/api/v1/complaint-agencies/'.ComplaintAgency::query()->first()->slug)->assertOk();

            return count(DB::getQueryLog());
        };

        $this->getJson('/api/v1/complaint-agencies')->assertOk(); // warm-up: query one-off (auth/session)

        $this->assertSame($count(2), $count(6));
    }
}
