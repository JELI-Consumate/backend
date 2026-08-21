<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ModuleProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModuleProgress
 */
final class ModuleProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'module_page_id' => $this->module_page_id,
            'status' => $this->status->value,
            'last_position' => $this->last_position,
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
