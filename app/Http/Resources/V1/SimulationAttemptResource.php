<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\SimulationAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Koreksi per item HANYA relevan di response check ini — GET /simulations/{id}
 * (Content\SimulationContentResource) tidak pernah menyertakan correct_position.
 *
 * Duolingo-style: matching_review/ordering_review cuma berisi item yang SUDAH
 * pernah dijawab benar sejauh ini (jawaban salah tidak pernah disimpan, jadi
 * tidak pernah muncul di sini) — is_correct akan selalu true untuk tiap baris.
 * score/max_score/is_passed/completed_at tetap null sampai seluruh item terjawab.
 *
 * @mixin SimulationAttempt
 */
final class SimulationAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'attempt_id' => $this->id,
            'simulation_content_id' => $this->simulation_content_id,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'is_passed' => $this->is_passed,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'matching_review' => $this->whenLoaded('matchingAnswers', fn () => $this->matchingAnswers->map(fn ($answer): array => [
                'simulation_matching_pair_id' => $answer->simulation_matching_pair_id,
                'submitted_right_pair_id' => $answer->submitted_right_pair_id,
                'is_correct' => $answer->is_correct,
            ])),
            'ordering_review' => $this->whenLoaded('orderingAnswers', fn () => $this->orderingAnswers->map(fn ($answer): array => [
                'simulation_ordering_step_id' => $answer->simulation_ordering_step_id,
                'submitted_position' => $answer->submitted_position,
                'correct_position' => $answer->orderingStep->correct_position,
                'is_correct' => $answer->is_correct,
            ])),
        ];
    }
}
