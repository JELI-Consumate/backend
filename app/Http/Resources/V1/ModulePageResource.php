<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\ContentableType;
use App\Enums\ProgressStatus;
use App\Http\Resources\V1\Content\ArticleContentResource;
use App\Http\Resources\V1\Content\QuizContentResource;
use App\Http\Resources\V1\Content\ReflectionContentResource;
use App\Http\Resources\V1\Content\SimulationContentResource;
use App\Http\Resources\V1\Content\VideoContentResource;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModulePage
 */
final class ModulePageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ModuleProgress|null $progress */
        $progress = $this->user_progress;

        return [
            'id' => $this->id,
            'order' => $this->order,
            'content_type' => $this->contentable_type,
            'content' => $this->whenLoaded('contentable', fn () => match ($this->contentable_type) {
                ContentableType::Video->value => new VideoContentResource($this->contentable),
                ContentableType::Article->value => new ArticleContentResource($this->contentable),
                ContentableType::Quiz->value => new QuizContentResource($this->contentable),
                ContentableType::Simulation->value => new SimulationContentResource($this->contentable),
                ContentableType::Reflection->value => new ReflectionContentResource($this->contentable),
            }),
            'progress' => [
                'status' => $progress?->status->value ?? ProgressStatus::NotStarted->value,
                'last_position' => $progress?->last_position ?? 0,
            ],
        ];
    }
}
