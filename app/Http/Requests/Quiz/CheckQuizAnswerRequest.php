<?php

declare(strict_types=1);

namespace App\Http\Requests\Quiz;

use App\Data\QuizAnswerCheckData;
use App\Enums\QuizSegmentType;
use App\Models\QuizAttempt;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

final class CheckQuizAnswerRequest extends FormRequest
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
            'type' => ['required', 'in:multiple_choice,likert'],
            'quiz_question_id' => ['required', 'string', 'ulid'],
            'quiz_choice_option_id' => ['required_if:type,multiple_choice', 'string', 'ulid'],
            'likert_scale_option_id' => ['required_if:type,likert', 'string', 'ulid'],
        ];
    }

    /**
     * Sama seperti `SubmitQuizAttemptRequest`: seluruh pohon kuis di-preload
     * SEKALI (nested eager load), keanggotaan question/option dicek di memori
     * — bukan `exists` query per field.
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

            $questionId = $this->input('quiz_question_id');
            $matchedSegment = null;
            $matchedQuestion = null;

            foreach ($attempt->quizContent->segments as $segment) {
                $question = $segment->questions->firstWhere('id', $questionId);

                if ($question !== null) {
                    $matchedSegment = $segment;
                    $matchedQuestion = $question;
                    break;
                }
            }

            if ($matchedQuestion === null) {
                $validator->errors()->add('quiz_question_id', 'Pertanyaan tidak valid untuk kuis ini.');

                return;
            }

            if ($this->input('type') === 'multiple_choice') {
                $validOptionIds = $matchedQuestion->choiceOptions->pluck('id')->all();

                if (! in_array($this->input('quiz_choice_option_id'), $validOptionIds, true)) {
                    $validator->errors()->add('quiz_choice_option_id', 'Jawaban tidak valid untuk soal ini.');
                }

                return;
            }

            $validOptionIds = $matchedSegment->likertScaleOptions->pluck('id')->all();

            if (! in_array($this->input('likert_scale_option_id'), $validOptionIds, true)) {
                $validator->errors()->add('likert_scale_option_id', 'Jawaban tidak valid untuk soal ini.');
            }
        });
    }

    public function toData(): QuizAnswerCheckData
    {
        return new QuizAnswerCheckData(
            type: QuizSegmentType::from($this->validated('type')),
            quizQuestionId: $this->validated('quiz_question_id'),
            quizChoiceOptionId: $this->validated('quiz_choice_option_id'),
            likertScaleOptionId: $this->validated('likert_scale_option_id'),
        );
    }
}
