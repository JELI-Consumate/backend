<?php

declare(strict_types=1);

namespace App\Http\Requests\Quiz;

use App\Data\QuizSubmissionData;
use App\Enums\QuizSegmentType;
use App\Models\QuizAttempt;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitQuizAttemptRequest extends FormRequest
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
            'choice_answers' => ['array'],
            'choice_answers.*.quiz_question_id' => ['required', 'integer'],
            'choice_answers.*.quiz_choice_option_id' => ['required', 'integer'],
            'likert_answers' => ['array'],
            'likert_answers.*.quiz_question_id' => ['required', 'integer'],
            'likert_answers.*.likert_scale_option_id' => ['required', 'integer'],
        ];
    }

    /**
     * Custom "whereIn" validation: seluruh pertanyaan/opsi di-preload SEKALI
     * (nested eager load), lalu dicek keanggotaannya di memori — bukan `exists`
     * per baris jawaban (05-service-layer-code.md §7).
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            $attempt = QuizAttempt::query()
                ->with(['quizContent.segments.questions.choiceOptions', 'quizContent.segments.likertScaleOptions'])
                ->find($this->route('id'));

            if ($attempt === null) {
                return;
            }

            $validChoiceOptionIdsByQuestion = [];
            $validLikertOptionIdsByQuestion = [];

            foreach ($attempt->quizContent->segments as $segment) {
                foreach ($segment->questions as $question) {
                    if ($segment->segment_type === QuizSegmentType::MultipleChoice) {
                        $validChoiceOptionIdsByQuestion[$question->id] = $question->choiceOptions->pluck('id')->all();
                    } else {
                        $validLikertOptionIdsByQuestion[$question->id] = $segment->likertScaleOptions->pluck('id')->all();
                    }
                }
            }

            foreach ($this->input('choice_answers', []) as $index => $answer) {
                $validOptionIds = $validChoiceOptionIdsByQuestion[$answer['quiz_question_id'] ?? null] ?? null;

                if ($validOptionIds === null || ! in_array($answer['quiz_choice_option_id'] ?? null, $validOptionIds, true)) {
                    $validator->errors()->add("choice_answers.{$index}", 'Jawaban tidak valid untuk soal ini.');
                }
            }

            foreach ($this->input('likert_answers', []) as $index => $answer) {
                $validOptionIds = $validLikertOptionIdsByQuestion[$answer['quiz_question_id'] ?? null] ?? null;

                if ($validOptionIds === null || ! in_array($answer['likert_scale_option_id'] ?? null, $validOptionIds, true)) {
                    $validator->errors()->add("likert_answers.{$index}", 'Jawaban tidak valid untuk soal ini.');
                }
            }
        });
    }

    public function toData(): QuizSubmissionData
    {
        return new QuizSubmissionData(
            $this->validated('choice_answers') ?? [],
            $this->validated('likert_answers') ?? [],
        );
    }
}
