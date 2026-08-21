<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Content;

use App\Models\QuizContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mode "soal" — tidak pernah menyertakan is_correct / explanation.
 * Mode "pembahasan" (Fase 5, setelah attempt selesai) pakai resource terpisah.
 *
 * @mixin QuizContent
 */
final class QuizContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'passing_score' => $this->passing_score,
            'shuffle_questions' => $this->shuffle_questions,
            'segments' => $this->whenLoaded('segments', fn () => $this->segments->map(fn ($segment): array => [
                'id' => $segment->id,
                'segment_type' => $segment->segment_type->value,
                'title' => $segment->title,
                'instruction' => $segment->instruction,
                'order' => $segment->order,
                'questions' => $segment->relationLoaded('questions')
                    ? $segment->questions->map(fn ($question): array => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'order' => $question->order,
                        'choice_options' => $question->relationLoaded('choiceOptions')
                            ? $question->choiceOptions->map(fn ($option): array => [
                                'id' => $option->id,
                                'option_text' => $option->option_text,
                                'order' => $option->order,
                            ])
                            : [],
                    ])
                    : [],
                'likert_scale_options' => $segment->relationLoaded('likertScaleOptions')
                    ? $segment->likertScaleOptions->map(fn ($option): array => [
                        'id' => $option->id,
                        'value' => $option->value,
                        'label' => $option->label,
                        'order' => $option->order,
                    ])
                    : [],
            ])),
        ];
    }
}
