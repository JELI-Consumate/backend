<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Module
 */
final class ModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'estimated_minutes' => $this->estimated_minutes,
            'is_required' => $this->is_required,
            'pages' => ModulePageResource::collection($this->whenLoaded('pages')),
        ];
    }
}
