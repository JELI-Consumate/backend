<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representasi ringan journey (dipakai di dalam daftar sektor) — tanpa daftar module.
 *
 * @mixin Journey
 */
class JourneyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var JourneyProgress|null $progress */
        $progress = $this->user_progress;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => MediaUrl::resolve($this->image_url),
            'order' => $this->order,
            'estimated_minutes' => $this->estimated_minutes,
            'is_unlocked' => (bool) $this->is_unlocked,
            'modules_count' => $this->relationLoaded('modules')
                ? $this->modules->count()
                : (int) ($this->modules_count ?? 0),
            'progress' => [
                'status' => $progress?->status->value ?? ProgressStatus::NotStarted->value,
                'percent' => $progress?->progress_percent ?? 0,
            ],
        ];
    }
}
