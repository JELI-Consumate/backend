<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ComplaintChannelType;
use App\Filament\Resources\ComplaintAgencies\Pages\CreateComplaintAgency;
use App\Filament\Resources\ComplaintAgencies\Pages\EditComplaintAgency;
use App\Filament\Resources\ComplaintAgencies\RelationManagers\ChannelsRelationManager;
use App\Filament\Resources\ComplaintCategories\Pages\CreateComplaintCategory;
use App\Models\ComplaintAgency;
use App\Models\ComplaintAgencyChannel;
use App\Models\ComplaintCategory;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ComplaintAgencyResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validForm(array $override = []): array
    {
        return array_merge([
            'short_name' => 'BPOM',
            'slug' => 'bpom',
            'full_name' => 'Badan Pengawas Obat dan Makanan',
            'summary' => 'Pengaduan obat dan makanan.',
            'description' => 'Deskripsi lengkap.',
            'order' => 1,
            'is_active' => true,
        ], $override);
    }

    public function test_super_admin_can_render_list_and_create_pages(): void
    {
        $admin = User::factory()->admin()->create();
        ComplaintAgency::factory()->create(['short_name' => 'LEMBAGAUJI']);

        $this->actingAs($admin)->get('/admin/complaint-agencies')->assertOk()->assertSee('LEMBAGAUJI');
        $this->actingAs($admin)->get('/admin/complaint-agencies/create')->assertOk();
        $this->actingAs($admin)->get('/admin/complaint-categories')->assertOk();
    }

    public function test_super_admin_creates_agency_with_when_to_contact_and_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ComplaintCategory::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateComplaintAgency::class)
            ->fillForm($this->validForm([
                'when_to_contact' => [['item' => 'Produk ilegal'], ['item' => 'Kosmetik berbahaya']],
                'categories' => [$category->id],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $agency = ComplaintAgency::query()->where('slug', 'bpom')->firstOrFail();
        $this->assertSame(['Produk ilegal', 'Kosmetik berbahaya'], array_values($agency->when_to_contact));
        $this->assertTrue($agency->categories->contains($category));
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        ComplaintAgency::factory()->create(['slug' => 'bpom']);

        Livewire::actingAs($admin)
            ->test(CreateComplaintAgency::class)
            ->fillForm($this->validForm())
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_super_admin_creates_category_with_agencies(): void
    {
        $admin = User::factory()->admin()->create();
        $agency = ComplaintAgency::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateComplaintCategory::class)
            ->fillForm(['problem' => 'Kendala QRIS', 'order' => 1, 'is_active' => true, 'agencies' => [$agency->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = ComplaintCategory::query()->where('problem', 'Kendala QRIS')->firstOrFail();
        $this->assertTrue($category->agencies->contains($agency));
    }

    public function test_sector_admin_can_view_list_but_not_create_or_edit(): void
    {
        $sectorAdmin = User::factory()->sectorAdmin(Sector::factory()->create())->create();
        $agency = ComplaintAgency::factory()->create(['short_name' => 'LEMBAGAUJI']);

        $this->actingAs($sectorAdmin)->get('/admin/complaint-agencies')->assertOk()->assertSee('LEMBAGAUJI');
        $this->actingAs($sectorAdmin)->get('/admin/complaint-agencies/create')->assertForbidden();
        $this->actingAs($sectorAdmin)->get("/admin/complaint-agencies/{$agency->id}/edit")->assertForbidden();
        $this->actingAs($sectorAdmin)->get('/admin/complaint-categories/create')->assertForbidden();
    }

    public function test_channels_relation_manager_creates_whatsapp_channel(): void
    {
        $admin = User::factory()->admin()->create();
        $agency = ComplaintAgency::factory()->create();

        Livewire::actingAs($admin)
            ->test(ChannelsRelationManager::class, ['ownerRecord' => $agency, 'pageClass' => EditComplaintAgency::class])
            ->callTableAction('create', data: [
                'type' => ComplaintChannelType::Whatsapp->value,
                'label' => 'WhatsApp Halo BPOM',
                'value' => '0811-9181-533',
                'button_label' => 'Hubungi Halo BPOM',
                'order' => 1,
            ])
            ->assertHasNoFormErrors();

        $channel = ComplaintAgencyChannel::query()->firstOrFail();
        $this->assertSame('https://wa.me/628119181533', $channel->url());
    }

    public function test_channels_relation_manager_rejects_unsafe_or_invalid_values(): void
    {
        $admin = User::factory()->admin()->create();
        $agency = ComplaintAgency::factory()->create();

        $mount = fn () => Livewire::actingAs($admin)
            ->test(ChannelsRelationManager::class, ['ownerRecord' => $agency, 'pageClass' => EditComplaintAgency::class]);

        $mount()->callTableAction('create', data: [
            'type' => ComplaintChannelType::Website->value,
            'label' => 'Situs',
            'value' => 'javascript:alert(1)',
            'order' => 1,
        ])->assertHasFormErrors(['value']);

        $mount()->callTableAction('create', data: [
            'type' => ComplaintChannelType::Whatsapp->value,
            'label' => 'WA',
            'value' => 'abc',
            'order' => 1,
        ])->assertHasFormErrors(['value']);

        $this->assertSame(0, ComplaintAgencyChannel::query()->count());
    }

    public function test_channels_relation_manager_is_hidden_from_sector_admin(): void
    {
        $sectorAdmin = User::factory()->sectorAdmin(Sector::factory()->create())->create();
        $agency = ComplaintAgency::factory()->create();

        $this->actingAs($sectorAdmin);

        $this->assertFalse(ChannelsRelationManager::canViewForRecord($agency, EditComplaintAgency::class));
    }
}
