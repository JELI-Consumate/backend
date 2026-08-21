<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Content;

use App\Models\ReflectionContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReflectionContent
 */
final class ReflectionContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'opening_message' => $this->opening_message,
            'closing_title' => $this->closing_title,
            'closing_message' => $this->closing_message,
            'sections' => $this->whenLoaded('sections', fn () => $this->sections->map(fn ($section): array => [
                'id' => $section->id,
                'title' => $section->title,
                'instruction' => $section->instruction,
                'order' => $section->order,
                'questions' => $section->relationLoaded('questions')
                    ? $section->questions->map(fn ($question): array => [
                        'id' => $question->id,
                        'question_type' => $question->question_type->value,
                        'question_text' => $question->question_text,
                        'order' => $question->order,
                    ])
                    : [],
            ])),
        ];
    }
}
