<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PublishStatus;
use App\Filament\Exports\ReflectionEntryExporter;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ReflectionContent;
use App\Models\ReflectionEntry;
use App\Models\ReflectionQuestion;
use App\Models\ReflectionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReflectionEntryExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_data_page_loads_with_reflection_card(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/export-data')
            ->assertOk()
            ->assertSee('Jawaban Refleksi');
    }

    /**
     * @return array{0: ReflectionEntry, 1: ReflectionQuestion, 2: ReflectionContent, 3: Module, 4: Journey}
     */
    private function activeEntry(string $answer = 'Jawaban saya'): array
    {
        $journey = Journey::factory()->create();
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $content = ReflectionContent::factory()->create();
        ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'reflection',
            'contentable_id' => $content->id,
        ]);
        $section = ReflectionSection::factory()->create(['reflection_content_id' => $content->id]);
        $question = ReflectionQuestion::factory()->create(['reflection_section_id' => $section->id]);
        $entry = ReflectionEntry::factory()->create([
            'reflection_question_id' => $question->id,
            'answer_text' => $answer,
        ]);

        return [$entry, $question, $content, $module, $journey];
    }

    /**
     * @return list<string>
     */
    private function exportedIds(): array
    {
        return ReflectionEntryExporter::modifyQuery(ReflectionEntry::query())->pluck('id')->all();
    }

    public function test_includes_active_filled_answer_with_full_context(): void
    {
        [$entry, , , $module, $journey] = $this->activeEntry();

        $this->assertSame([$entry->id], $this->exportedIds());

        $record = ReflectionEntryExporter::modifyQuery(ReflectionEntry::query())->findOrFail($entry->id);
        $this->assertSame($journey->title, $record->reflectionQuestion->reflectionSection->reflectionContent->modulePage->module->journey->title);
        $this->assertSame($module->title, $record->reflectionQuestion->reflectionSection->reflectionContent->modulePage->module->title);
    }

    public function test_excludes_empty_answers_and_admin_answers(): void
    {
        [$filled] = $this->activeEntry();
        [$empty] = $this->activeEntry('');
        [$admin] = $this->activeEntry('Jawaban admin');
        $admin->update(['user_id' => User::factory()->admin()->create()->id]);

        $this->assertSame([$filled->id], $this->exportedIds());
        $this->assertNotContains($empty->id, $this->exportedIds());
    }

    public function test_excludes_soft_deleted_question_section_content_module_and_journey(): void
    {
        [$keep] = $this->activeEntry();
        [, $question] = $this->activeEntry();
        [, , $content] = $this->activeEntry();
        [, , , $module] = $this->activeEntry();
        [, , , , $journey] = $this->activeEntry();

        $question->delete();
        $question->reflectionSection->delete();
        $content->delete();
        $module->delete();
        $journey->delete();

        $this->assertSame([$keep->id], $this->exportedIds());
    }

    public function test_excludes_content_detached_from_module_and_draft_module_or_journey(): void
    {
        [$keep] = $this->activeEntry();
        [, , $detached] = $this->activeEntry();
        [, , , $draftModule] = $this->activeEntry();
        [, , , , $draftJourney] = $this->activeEntry();

        $detached->modulePage()->delete();
        $draftModule->update(['status' => PublishStatus::Draft]);
        $draftJourney->update(['status' => PublishStatus::Draft]);

        $this->assertSame([$keep->id], $this->exportedIds());
    }

    public function test_columns_have_labels(): void
    {
        foreach (ReflectionEntryExporter::getColumns() as $column) {
            $this->assertNotEmpty($column->getLabel());
        }
    }
}
