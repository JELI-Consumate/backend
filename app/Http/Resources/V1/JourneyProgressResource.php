<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
final class JourneyProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var JourneyProgress|null $progress */
        $progress = $this->user_progress;

        return [
            'journey_id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'status' => $progress?->status->value ?? ProgressStatus::NotStarted->value,
            'progress_percent' => $progress?->progress_percent ?? 0,
            'completed_at' => $progress?->completed_at?->toIso8601String(),
        ];
    }
}
