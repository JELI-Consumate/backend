<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\ArticleContents\Pages\ListArticleContents;
use App\Filament\Resources\QuizContents\Pages\ListQuizContents;
use App\Filament\Resources\ReflectionContents\Pages\ListReflectionContents;
use App\Filament\Resources\SimulationContents\Pages\ListSimulationContents;
use App\Filament\Resources\VideoContents\Pages\ListVideoContents;
use App\Models\ArticleBlock;
use App\Models\ArticleContent;
use App\Models\QuizChoiceOption;
use App\Models\QuizContent;
use App\Models\QuizQuestion;
use App\Models\QuizSegment;
use App\Models\ReflectionContent;
use App\Models\ReflectionQuestion;
use App\Models\ReflectionSection;
use App\Models\SimulationContent;
use App\Models\SimulationMatchingPair;
use App\Models\SimulationOrderingStep;
use App\Models\User;
use App\Models\VideoContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 9: preview builder konten harus bisa dibuka tanpa error 500 (mis.
 * lazy-loading exception di relasi bertingkat), untuk video/artikel/kuis/
 * simulasi/refleksi — sesuai catatan UX admin di 06-nonfunctional-ops.md §10.
 */
final class ContentPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_preview_action_renders(): void
    {
        $this->actingAs(User::factory()->create());
        $video = VideoContent::factory()->create(['youtube_url' => 'https://www.youtube.com/watch?v=abc12345678']);

        Livewire::test(ListVideoContents::class)
            ->callTableAction('preview', $video)
            ->assertSuccessful();
    }

    public function test_article_preview_action_renders(): void
    {
        $this->actingAs(User::factory()->create());
        $article = ArticleContent::factory()->create();
        ArticleBlock::factory()->create(['article_content_id' => $article->id]);

        Livewire::test(ListArticleContents::class)
            ->callTableAction('preview', $article)
            ->assertSuccessful();
    }

    public function test_quiz_preview_action_renders(): void
    {
        $this->actingAs(User::factory()->create());
        $quiz = QuizContent::factory()->create();
        $segment = QuizSegment::factory()->create(['quiz_content_id' => $quiz->id]);
        $question = QuizQuestion::factory()->create(['quiz_segment_id' => $segment->id]);
        QuizChoiceOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => true]);
        QuizChoiceOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);
        QuizSegment::factory()->likert()->create(['quiz_content_id' => $quiz->id]);

        Livewire::test(ListQuizContents::class)
            ->callTableAction('preview', $quiz->fresh())
            ->assertSuccessful();
    }

    public function test_simulation_preview_action_renders(): void
    {
        $this->actingAs(User::factory()->create());
        $simulation = SimulationContent::factory()->create();
        SimulationMatchingPair::factory()->create(['simulation_content_id' => $simulation->id]);
        SimulationOrderingStep::factory()->create(['simulation_content_id' => $simulation->id]);

        Livewire::test(ListSimulationContents::class)
            ->callTableAction('preview', $simulation->fresh())
            ->assertSuccessful();
    }

    public function test_reflection_preview_action_renders(): void
    {
        $this->actingAs(User::factory()->create());
        $reflection = ReflectionContent::factory()->create();
        $section = ReflectionSection::factory()->create(['reflection_content_id' => $reflection->id]);
        ReflectionQuestion::factory()->create(['reflection_section_id' => $section->id]);

        Livewire::test(ListReflectionContents::class)
            ->callTableAction('preview', $reflection->fresh())
            ->assertSuccessful();
    }
}
