<?php

declare(strict_types=1);

namespace App\Http\Requests\Reflection;

use App\Data\ReflectionEntriesData;
use App\Models\ReflectionContent;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

final class StoreReflectionEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entries' => ['array'],
            'entries.*.reflection_question_id' => ['required', 'integer'],
            'entries.*.answer_text' => ['nullable', 'string'],
            'checklist_answers' => ['array'],
            'checklist_answers.*.reflection_checklist_item_id' => ['required', 'integer'],
            'checklist_answers.*.is_checked' => ['required', 'boolean'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            $content = ReflectionContent::query()->with('sections.questions.checklistItems')->find($this->route('id'));

            if ($content === null) {
                return;
            }

            $questions = $content->sections->flatMap(fn ($section) => $section->questions);
            $validQuestionIds = $questions->pluck('id')->all();
            $validChecklistItemIds = $questions->flatMap(fn ($question) => $question->checklistItems)->pluck('id')->all();

            foreach ($this->input('entries', []) as $index => $entry) {
                if (! in_array($entry['reflection_question_id'] ?? null, $validQuestionIds, true)) {
                    $validator->errors()->add("entries.{$index}", 'Pertanyaan tidak valid untuk refleksi ini.');
                }
            }

            foreach ($this->input('checklist_answers', []) as $index => $answer) {
                if (! in_array($answer['reflection_checklist_item_id'] ?? null, $validChecklistItemIds, true)) {
                    $validator->errors()->add("checklist_answers.{$index}", 'Item checklist tidak valid untuk refleksi ini.');
                }
            }
        });
    }

    public function toData(): ReflectionEntriesData
    {
        return new ReflectionEntriesData(
            $this->validated('entries') ?? [],
            $this->validated('checklist_answers') ?? [],
        );
    }
}
