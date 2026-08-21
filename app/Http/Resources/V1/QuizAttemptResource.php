<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mode "pembahasan" — is_correct/correct_option_id/explanation HANYA muncul
 * setelah attempt selesai (completed_at != null). Lihat 06 §9.3.
 *
 * @mixin QuizAttempt
 */
final class QuizAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isCompleted = $this->completed_at !== null;
        $percentage = $isCompleted && $this->choice_max_score > 0
            ? intdiv($this->choice_score * 100, $this->choice_max_score)
            : null;

        return [
            'attempt_id' => $this->id,
            'quiz_content_id' => $this->quiz_content_id,
            'attempt_number' => $this->attempt_number,
            'choice_score' => $this->choice_score,
            'choice_max_score' => $this->choice_max_score,
            'percentage' => $percentage,
            'passed' => $this->passed,
            'likert_average' => $this->likert_average !== null ? (float) $this->likert_average : null,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'review' => $this->when(
                $isCompleted,
                fn () => $this->whenLoaded('choiceAnswers', fn () => $this->choiceAnswers->map(fn ($answer): array => [
                    'quiz_question_id' => $answer->quiz_question_id,
                    'question' => $answer->quizQuestion->question,
                    'selected_option_id' => $answer->quiz_choice_option_id,
                    'correct_option_id' => $answer->quizQuestion->choiceOptions->firstWhere('is_correct', true)?->id,
                    'is_correct' => $answer->is_correct,
                    'explanation' => $answer->quizQuestion->explanation,
                ]))
            ),
        ];
    }
}
