<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\ProgressStatus;
use App\Models\Sector;
use App\Models\SectorProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sector
 */
final class SectorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SectorProgress|null $progress */
        $progress = $this->user_progress;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'icon_url' => $this->icon_url,
            'color' => $this->color,
            'order' => $this->order,
            'progress' => [
                'status' => $progress?->status->value ?? ProgressStatus::NotStarted->value,
                'percent' => $progress?->progress_percent ?? 0,
            ],
            'journeys' => $this->when(
                array_key_exists('journey_list', $this->resource->getAttributes()),
                fn () => JourneyResource::collection($this->journey_list)
            ),
        ];
    }
}
