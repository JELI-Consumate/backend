<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Content;

use App\Models\SimulationContent;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `correct_position` (simulation_ordering_steps) tidak pernah disertakan di sini —
 * lihat 06-nonfunctional-ops.md §9.3.
 *
 * @mixin SimulationContent
 */
final class SimulationContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'simulation_type' => $this->simulation_type->value,
            'scenario' => $this->scenario,
            'matching_pairs' => $this->whenLoaded('matchingPairs', fn () => $this->matchingPairs->map(fn ($pair): array => [
                'id' => $pair->id,
                'left_label' => $pair->left_label,
                'left_description' => $pair->left_description,
                'left_image_url' => MediaUrl::resolve($pair->left_image_url),
                'right_label' => $pair->right_label,
                'right_description' => $pair->right_description,
                'right_image_url' => MediaUrl::resolve($pair->right_image_url),
                'order' => $pair->order,
            ])),
            'ordering_steps' => $this->whenLoaded('orderingSteps', fn () => $this->orderingSteps->map(fn ($step): array => [
                'id' => $step->id,
                'label' => $step->label,
                'image_url' => MediaUrl::resolve($step->image_url),
                'order' => $step->order,
            ])),
        ];
    }
}
