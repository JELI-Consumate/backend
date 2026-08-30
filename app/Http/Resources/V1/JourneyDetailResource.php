<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

final class JourneyDetailResource extends JourneyResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'modules' => ModuleResource::collection($this->whenLoaded('modules')),
            'quiz_score' => $this->quiz_score,
        ];
    }
}
