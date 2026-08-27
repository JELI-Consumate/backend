<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\ArticleContents\Pages\CreateArticleContent;
use App\Models\ArticleContent;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tombol "Buat Konten Baru" di tab Pages (lihat PagesRelationManager) bikin
 * konten baru lewat form aslinya (bukan dropdown pilih yang sudah ada), lalu
 * otomatis nempelinnya jadi ModulePage. Lihat
 * App\Filament\Concerns\AttachesContentToModulePage.
 */
final class AttachContentToModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_content_with_module_id_attaches_it_as_a_module_page(): void
    {
        $admin = User::factory()->admin()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);

        Livewire::withQueryParams(['module_id' => $module->id])
            ->actingAs($admin)
            ->test(CreateArticleContent::class)
            ->fillForm(['title' => 'Artikel Baru', 'blocks' => []])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect("/admin/modules/{$module->id}/edit");

        $article = ArticleContent::where('title', 'Artikel Baru')->firstOrFail();

        $page = ModulePage::where('module_id', $module->id)->first();
        $this->assertNotNull($page);
        $this->assertSame('article', $page->contentable_type);
        $this->assertSame($article->id, $page->contentable_id);
        $this->assertSame(1, $page->order);
    }

    public function test_module_page_order_increments_for_each_new_page(): void
    {
        $admin = User::factory()->admin()->create();
        $module = Module::factory()->create();
        ModulePage::factory()->create(['module_id' => $module->id, 'order' => 1]);
        ModulePage::factory()->create(['module_id' => $module->id, 'order' => 2]);

        Livewire::withQueryParams(['module_id' => $module->id])
            ->actingAs($admin)
            ->test(CreateArticleContent::class)
            ->fillForm(['title' => 'Artikel Ketiga', 'blocks' => []])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = ArticleContent::where('title', 'Artikel Ketiga')->firstOrFail();
        $page = ModulePage::where('contentable_type', 'article')->where('contentable_id', $article->id)->firstOrFail();

        $this->assertSame(3, $page->order);
    }

    public function test_creating_content_without_module_id_does_not_attach_anything(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateArticleContent::class)
            ->fillForm(['title' => 'Artikel Mandiri', 'blocks' => []])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, ModulePage::count());
    }

    public function test_sector_admin_cannot_attach_content_to_a_module_outside_their_sector(): void
    {
        $ownSector = Sector::factory()->create();
        $otherSector = Sector::factory()->create();
        $otherModule = Module::factory()->create([
            'journey_id' => Journey::factory()->create(['sector_id' => $otherSector->id])->id,
        ]);

        $admin = User::factory()->sectorAdmin($ownSector)->create();

        Livewire::withQueryParams(['module_id' => $otherModule->id])
            ->actingAs($admin)
            ->test(CreateArticleContent::class)
            ->fillForm(['title' => 'Artikel Nakal', 'blocks' => []])
            ->call('create')
            ->assertHasNoFormErrors();

        // Kontennya tetap dibuat, tapi tidak boleh ketempel ke module sector lain.
        $this->assertDatabaseHas('article_contents', ['title' => 'Artikel Nakal']);
        $this->assertSame(0, ModulePage::where('module_id', $otherModule->id)->count());
    }
}
