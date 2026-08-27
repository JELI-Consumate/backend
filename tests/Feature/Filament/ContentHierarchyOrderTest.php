<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\QuizKind;
use App\Filament\Resources\ArticleContents\Pages\ListArticleContents;
use App\Filament\Resources\Journeys\Pages\ListJourneys;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\QuizContents\Pages\ListQuizContents;
use App\Models\ArticleContent;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizContent;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Daftar Journey/Module/konten reusable harus tampil urut mengikuti posisi
 * induknya di hierarki Sector -> Journey -> Module (bukan title/id), dan
 * konten yang belum ditempel ke module manapun ditaruh paling akhir.
 */
final class ContentHierarchyOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_contents_are_listed_by_parent_hierarchy_with_unattached_last(): void
    {
        $admin = User::factory()->admin()->create();

        $sectorA = Sector::factory()->create(['order' => 1]);
        $sectorB = Sector::factory()->create(['order' => 2]);

        $journeyA1 = Journey::factory()->create(['sector_id' => $sectorA->id, 'order' => 1]);
        $journeyA2 = Journey::factory()->create(['sector_id' => $sectorA->id, 'order' => 2]);
        $journeyB1 = Journey::factory()->create(['sector_id' => $sectorB->id, 'order' => 1]);

        $moduleA1 = Module::factory()->create(['journey_id' => $journeyA1->id, 'order' => 1]);
        $moduleA2 = Module::factory()->create(['journey_id' => $journeyA2->id, 'order' => 1]);
        $moduleB1 = Module::factory()->create(['journey_id' => $journeyB1->id, 'order' => 1]);

        // Judul sengaja dibuat berkebalikan dari urutan hierarki, supaya
        // test ini gagal kalau ordering diam-diam balik ke urutan title/id.
        $articleInSectorA1 = ArticleContent::factory()->create(['title' => 'Z - Sector A Journey 1']);
        ModulePage::factory()->create(['module_id' => $moduleA1->id, 'order' => 1, 'contentable_type' => 'article', 'contentable_id' => $articleInSectorA1->id]);

        $articleInSectorA2 = ArticleContent::factory()->create(['title' => 'Y - Sector A Journey 2']);
        ModulePage::factory()->create(['module_id' => $moduleA2->id, 'order' => 1, 'contentable_type' => 'article', 'contentable_id' => $articleInSectorA2->id]);

        $articleInSectorB1 = ArticleContent::factory()->create(['title' => 'X - Sector B Journey 1']);
        ModulePage::factory()->create(['module_id' => $moduleB1->id, 'order' => 1, 'contentable_type' => 'article', 'contentable_id' => $articleInSectorB1->id]);

        $unattached = ArticleContent::factory()->create(['title' => 'A - Belum Ditempel']);

        Livewire::actingAs($admin)
            ->test(ListArticleContents::class)
            ->assertSeeInOrder([
                $articleInSectorA1->title,
                $articleInSectorA2->title,
                $articleInSectorB1->title,
                $unattached->title,
            ]);
    }

    public function test_journeys_are_listed_by_sector_order_then_own_order(): void
    {
        $admin = User::factory()->admin()->create();

        $sectorA = Sector::factory()->create(['order' => 1]);
        $sectorB = Sector::factory()->create(['order' => 2]);

        $journeyB1 = Journey::factory()->create(['sector_id' => $sectorB->id, 'order' => 1, 'title' => 'Journey B1']);
        $journeyA2 = Journey::factory()->create(['sector_id' => $sectorA->id, 'order' => 2, 'title' => 'Journey A2']);
        $journeyA1 = Journey::factory()->create(['sector_id' => $sectorA->id, 'order' => 1, 'title' => 'Journey A1']);

        Livewire::actingAs($admin)
            ->test(ListJourneys::class)
            ->assertSeeInOrder([$journeyA1->title, $journeyA2->title, $journeyB1->title]);
    }

    public function test_modules_are_listed_by_sector_order_then_journey_order_then_own_order(): void
    {
        $admin = User::factory()->admin()->create();

        $sector = Sector::factory()->create();
        $journey1 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $journey2 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);

        $moduleJourney2 = Module::factory()->create(['journey_id' => $journey2->id, 'order' => 1, 'title' => 'Module Journey2-1']);
        $moduleJourney1Second = Module::factory()->create(['journey_id' => $journey1->id, 'order' => 2, 'title' => 'Module Journey1-2']);
        $moduleJourney1First = Module::factory()->create(['journey_id' => $journey1->id, 'order' => 1, 'title' => 'Module Journey1-1']);

        Livewire::actingAs($admin)
            ->test(ListModules::class)
            ->assertSeeInOrder([
                $moduleJourney1First->title,
                $moduleJourney1Second->title,
                $moduleJourney2->title,
            ]);
    }

    public function test_quiz_contents_order_pretest_before_journeys_before_posttest_within_a_sector(): void
    {
        $admin = User::factory()->admin()->create();

        $sector = Sector::factory()->create();
        $journey1 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1, 'title' => 'Journey Pertama']);
        $journey2 = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2, 'title' => 'Journey Kedua']);

        QuizContent::factory()->posttest()->create(['sector_id' => $sector->id, 'passing_score' => 70]);
        QuizContent::factory()->create(['kind' => QuizKind::Quiz, 'journey_id' => $journey2->id]);
        QuizContent::factory()->create(['kind' => QuizKind::Quiz, 'journey_id' => $journey1->id]);
        QuizContent::factory()->pretest()->create(['sector_id' => $sector->id, 'passing_score' => 70]);

        Livewire::actingAs($admin)
            ->test(ListQuizContents::class)
            ->assertSeeInOrder([
                QuizKind::Pretest->value,
                $journey1->title,
                $journey2->title,
                QuizKind::Posttest->value,
            ]);
    }
}
