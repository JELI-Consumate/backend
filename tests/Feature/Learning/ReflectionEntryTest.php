<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\ReflectionQuestionType;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ReflectionChecklistItem;
use App\Models\ReflectionContent;
use App\Models\ReflectionQuestion;
use App\Models\ReflectionSection;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReflectionEntryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: ReflectionContent, 1: ModulePage, 2: ReflectionQuestion, 3: ReflectionQuestion}
     */
    private function createReflectionWithTwoOpenQuestions(Journey $journey): array
    {
        $content = ReflectionContent::factory()->create();
        $section = ReflectionSection::factory()->create(['reflection_content_id' => $content->id]);
        $q1 = ReflectionQuestion::factory()->create([
            'reflection_section_id' => $section->id,
            'question_type' => ReflectionQuestionType::OpenQuestion,
            'order' => 1,
        ]);
        $q2 = ReflectionQuestion::factory()->create([
            'reflection_section_id' => $section->id,
            'question_type' => ReflectionQuestionType::OpenQuestion,
            'order' => 2,
        ]);

        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'reflection',
            'contentable_id' => $content->id,
        ]);

        return [$content, $page, $q1, $q2];
    }

    public function test_show_returns_structure_with_previous_answers(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$content, , $q1] = $this->createReflectionWithTwoOpenQuestions($journey);

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $q1->id, 'answer_text' => 'Jawaban pertama']],
        ])->assertOk();

        $response = $this->actingAs($user)->getJson("/api/v1/reflections/{$content->id}");

        $response->assertOk()->assertJsonPath('data.sections.0.questions.0.answer_text', 'Jawaban pertama');
    }

    /**
     * BR-10: module refleksi selesai hanya setelah SELURUH open_question terisi.
     */
    public function test_module_page_completes_only_after_all_open_questions_answered(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$content, $page, $q1, $q2] = $this->createReflectionWithTwoOpenQuestions($journey);

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $q1->id, 'answer_text' => 'Jawaban 1']],
        ])->assertOk();

        $pageResponse = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");
        $pageResponse->assertOk()->assertJsonPath('data.progress.status', 'not_started');

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $q2->id, 'answer_text' => 'Jawaban 2']],
        ])->assertOk();

        $pageResponse = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");
        $pageResponse->assertOk()->assertJsonPath('data.progress.status', 'completed');
    }

    /**
     * BR-10: pertanyaan checklist tidak menghalangi completion — hanya open_question yang dihitung.
     */
    public function test_checklist_question_does_not_block_completion(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $content = ReflectionContent::factory()->create();
        $section = ReflectionSection::factory()->create(['reflection_content_id' => $content->id]);
        $openQuestion = ReflectionQuestion::factory()->create([
            'reflection_section_id' => $section->id,
            'question_type' => ReflectionQuestionType::OpenQuestion,
        ]);
        ReflectionQuestion::factory()->create([
            'reflection_section_id' => $section->id,
            'question_type' => ReflectionQuestionType::Checklist,
        ]);

        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'reflection',
            'contentable_id' => $content->id,
        ]);

        // Checklist sengaja tidak dijawab sama sekali.
        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $openQuestion->id, 'answer_text' => 'Selesai']],
        ])->assertOk();

        $pageResponse = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");
        $pageResponse->assertOk()->assertJsonPath('data.progress.status', 'completed');
    }

    public function test_upsert_is_idempotent_and_allows_editing_journal(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$content, , $q1] = $this->createReflectionWithTwoOpenQuestions($journey);

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $q1->id, 'answer_text' => 'Draft awal']],
        ])->assertOk();

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $q1->id, 'answer_text' => 'Draft revisi']],
        ])->assertOk();

        $this->assertDatabaseCount('reflection_entries', 1);
        $this->assertDatabaseHas('reflection_entries', [
            'reflection_question_id' => $q1->id,
            'answer_text' => 'Draft revisi',
        ]);
    }

    public function test_checklist_items_are_multi_item_and_dont_block_completion(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $content = ReflectionContent::factory()->create();
        $section = ReflectionSection::factory()->create(['reflection_content_id' => $content->id]);
        $openQuestion = ReflectionQuestion::factory()->create([
            'reflection_section_id' => $section->id,
            'question_type' => ReflectionQuestionType::OpenQuestion,
            'order' => 1,
        ]);
        $checklistQuestion = ReflectionQuestion::factory()->create([
            'reflection_section_id' => $section->id,
            'question_type' => ReflectionQuestionType::Checklist,
            'order' => 2,
        ]);
        $item1 = ReflectionChecklistItem::factory()->create(['reflection_question_id' => $checklistQuestion->id, 'order' => 1]);
        $item2 = ReflectionChecklistItem::factory()->create(['reflection_question_id' => $checklistQuestion->id, 'order' => 2]);

        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'reflection',
            'contentable_id' => $content->id,
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $openQuestion->id, 'answer_text' => 'Selesai']],
            'checklist_answers' => [
                ['reflection_checklist_item_id' => $item1->id, 'is_checked' => true],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.sections.0.questions.1.checklist_items.0.is_checked', true)
            ->assertJsonPath('data.sections.0.questions.1.checklist_items.1.is_checked', false);

        // Checklist sengaja cuma 1 dari 2 item dicentang — tetap harus completed (BR-10).
        $pageResponse = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");
        $pageResponse->assertOk()->assertJsonPath('data.progress.status', 'completed');

        $this->assertDatabaseHas('reflection_checklist_answers', [
            'user_id' => $user->id,
            'reflection_checklist_item_id' => $item1->id,
            'is_checked' => 1,
        ]);
        $this->assertDatabaseMissing('reflection_checklist_answers', [
            'reflection_checklist_item_id' => $item2->id,
        ]);
    }

    public function test_checklist_answer_toggle_is_idempotent(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$content] = $this->createReflectionWithTwoOpenQuestions($journey);

        $section = ReflectionSection::factory()->create(['reflection_content_id' => $content->id]);
        $checklistQuestion = ReflectionQuestion::factory()->create([
            'reflection_section_id' => $section->id,
            'question_type' => ReflectionQuestionType::Checklist,
        ]);
        $item = ReflectionChecklistItem::factory()->create(['reflection_question_id' => $checklistQuestion->id]);

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'checklist_answers' => [['reflection_checklist_item_id' => $item->id, 'is_checked' => true]],
        ])->assertOk();

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'checklist_answers' => [['reflection_checklist_item_id' => $item->id, 'is_checked' => false]],
        ])->assertOk();

        $this->assertDatabaseCount('reflection_checklist_answers', 1);
        $this->assertDatabaseHas('reflection_checklist_answers', [
            'reflection_checklist_item_id' => $item->id,
            'is_checked' => 0,
        ]);
    }

    public function test_entries_reject_question_from_another_reflection(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$content] = $this->createReflectionWithTwoOpenQuestions($journey);

        $otherContent = ReflectionContent::factory()->create();
        $otherSection = ReflectionSection::factory()->create(['reflection_content_id' => $otherContent->id]);
        $foreignQuestion = ReflectionQuestion::factory()->create(['reflection_section_id' => $otherSection->id]);

        $this->actingAs($user)->putJson("/api/v1/reflections/{$content->id}/entries", [
            'entries' => [['reflection_question_id' => $foreignQuestion->id, 'answer_text' => 'Bocor']],
        ])->assertStatus(422);
    }
}
