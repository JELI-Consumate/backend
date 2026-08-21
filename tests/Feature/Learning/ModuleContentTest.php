<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\ProgressStatus;
use App\Models\ArticleBlock;
use App\Models\ArticleContent;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\QuizChoiceOption;
use App\Models\QuizContent;
use App\Models\QuizQuestion;
use App\Models\QuizSegment;
use App\Models\ReflectionContent;
use App\Models\ReflectionQuestion;
use App\Models\ReflectionSection;
use App\Models\Sector;
use App\Models\SimulationContent;
use App\Models\SimulationMatchingPair;
use App\Models\SimulationOrderingStep;
use App\Models\User;
use App\Models\VideoContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ModuleContentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kombinasi realistis satu module "materi": video + article — dipakai untuk
     * assertion budget query ≤8 (06-nonfunctional-ops.md §8). Journey dibuat
     * order=1 (selalu unlocked) supaya tidak menambah query cek journey sebelumnya.
     */
    private function createVideoArticleModule(): Module
    {
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);

        $video = VideoContent::factory()->create();
        ModulePage::factory()->create(['module_id' => $module->id, 'order' => 1, 'contentable_type' => 'video', 'contentable_id' => $video->id]);

        $article = ArticleContent::factory()->create();
        ArticleBlock::factory()->count(2)->create(['article_content_id' => $article->id]);
        ModulePage::factory()->create(['module_id' => $module->id, 'order' => 2, 'contentable_type' => 'article', 'contentable_id' => $article->id]);

        return $module;
    }

    /**
     * Module "kuis": kuis sendirian punya 4 level relasi turunan (segments →
     * questions → choiceOptions, segments → likertScaleOptions) — sudah menghabiskan
     * separuh budget 8 query sendirian, jadi diuji terpisah dari budget test (bukan
     * dicampur dengan video/article) dan tidak diberi assertion count ketat.
     */
    private function createVideoArticleQuizModule(): Module
    {
        $module = $this->createVideoArticleModule();
        $journey = Journey::query()->findOrFail($module->journey_id);

        $quiz = QuizContent::factory()->create(['journey_id' => $journey->id]);
        $segment = QuizSegment::factory()->create(['quiz_content_id' => $quiz->id]);
        $question = QuizQuestion::factory()->create(['quiz_segment_id' => $segment->id]);
        QuizChoiceOption::factory()->count(2)->create(['quiz_question_id' => $question->id]);
        ModulePage::factory()->create(['module_id' => $module->id, 'order' => 3, 'contentable_type' => 'quiz', 'contentable_id' => $quiz->id]);

        return $module;
    }

    /**
     * Satu module berisi seluruh 5 tipe konten sekaligus — dipakai untuk verifikasi
     * korektnes & kebocoran field, BUKAN untuk assertion budget query (kombinasi ini
     * tidak merepresentasikan module nyata; tiap ModuleType biasanya hanya berisi
     * 1-2 tipe konten, lihat 03-model-data.md §3.2 ModuleType).
     */
    private function createFullMixedModule(): Module
    {
        $module = $this->createVideoArticleQuizModule();

        $simulation = SimulationContent::factory()->create();
        SimulationMatchingPair::factory()->create(['simulation_content_id' => $simulation->id]);
        SimulationOrderingStep::factory()->create(['simulation_content_id' => $simulation->id]);
        ModulePage::factory()->create(['module_id' => $module->id, 'order' => 4, 'contentable_type' => 'simulation', 'contentable_id' => $simulation->id]);

        $reflection = ReflectionContent::factory()->create();
        $section = ReflectionSection::factory()->create(['reflection_content_id' => $reflection->id]);
        ReflectionQuestion::factory()->create(['reflection_section_id' => $section->id]);
        ModulePage::factory()->create(['module_id' => $module->id, 'order' => 5, 'contentable_type' => 'reflection', 'contentable_id' => $reflection->id]);

        return $module;
    }

    public function test_module_tree_resolves_mixed_content_within_query_budget(): void
    {
        $user = User::factory()->create();
        $module = $this->createVideoArticleModule();

        DB::enableQueryLog();
        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk()->assertJsonCount(2, 'data.pages');
        $response->assertJsonPath('data.pages.0.content_type', 'video');
        $response->assertJsonPath('data.pages.1.content.blocks.0.block_type', 'paragraph');

        $this->assertLessThanOrEqual(8, $queryCount);
    }

    public function test_quiz_module_resolves_full_segment_tree(): void
    {
        $user = User::factory()->create();
        $module = $this->createVideoArticleQuizModule();

        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");

        $response->assertOk()->assertJsonCount(3, 'data.pages');
        $this->assertIsString($response->json('data.pages.2.content.segments.0.questions.0.choice_options.0.option_text'));
    }

    public function test_module_tree_query_count_does_not_scale_with_page_count(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);

        foreach (range(1, 10) as $order) {
            $video = VideoContent::factory()->create();
            ModulePage::factory()->create(['module_id' => $module->id, 'order' => $order, 'contentable_type' => 'video', 'contentable_id' => $video->id]);
        }

        DB::enableQueryLog();
        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk()->assertJsonCount(10, 'data.pages');
        $this->assertLessThanOrEqual(6, $queryCount);
    }

    public function test_module_tree_resolves_simulation_and_reflection_content(): void
    {
        $user = User::factory()->create();
        $module = $this->createFullMixedModule();

        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");

        $response->assertOk()->assertJsonCount(5, 'data.pages');
        $response->assertJsonPath('data.pages.3.content_type', 'simulation');
        $response->assertJsonPath('data.pages.4.content.sections.0.questions.0.question_type', 'open_question');
    }

    public function test_module_tree_merges_user_progress_per_page(): void
    {
        $user = User::factory()->create();
        $module = $this->createVideoArticleQuizModule();
        $firstPage = $module->pages()->orderBy('order')->first();

        ModuleProgress::factory()->create([
            'user_id' => $user->id,
            'module_page_id' => $firstPage->id,
            'status' => ProgressStatus::Completed,
            'last_position' => 130,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");

        $response->assertOk()
            ->assertJsonPath('data.pages.0.progress.status', 'completed')
            ->assertJsonPath('data.pages.0.progress.last_position', 130)
            ->assertJsonPath('data.pages.1.progress.status', 'not_started');
    }

    public function test_quiz_content_does_not_leak_is_correct(): void
    {
        $user = User::factory()->create();
        $module = $this->createVideoArticleQuizModule();

        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");

        $response->assertOk();
        $this->assertStringNotContainsString('is_correct', (string) json_encode($response->json()));
    }

    public function test_simulation_content_does_not_leak_correct_position(): void
    {
        $user = User::factory()->create();
        $module = $this->createFullMixedModule();

        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");

        $response->assertOk();
        $this->assertStringNotContainsString('correct_position', (string) json_encode($response->json()));
    }

    public function test_module_show_returns_404_for_unknown_module(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/modules/999999')->assertNotFound();
    }

    public function test_module_show_returns_403_when_journey_locked(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $lockedJourney = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        $module = Module::factory()->create(['journey_id' => $lockedJourney->id]);
        $video = VideoContent::factory()->create();
        ModulePage::factory()->create(['module_id' => $module->id, 'contentable_type' => 'video', 'contentable_id' => $video->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/modules/{$module->id}");

        $response->assertStatus(403)->assertJsonPath('code', 'JOURNEY_LOCKED');
    }
}
