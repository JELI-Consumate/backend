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
final class SectorProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SectorProgress|null $progress */
        $progress = $this->user_progress;

        return [
            'sector_id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'status' => $progress?->status->value ?? ProgressStatus::NotStarted->value,
            'progress_percent' => $progress?->progress_percent ?? 0,
            'completed_at' => $progress?->completed_at?->toIso8601String(),
        ];
    }
}
