<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\UserBadge;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserBadge|null $user_badge
 */
final class BadgeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'journey_id' => $this->journey_id,
            'name' => $this->name,
            'description' => $this->description,
            'congratulation_message' => $this->congratulation_message,
            'motivational_message' => $this->motivational_message,
            'icon_url' => MediaUrl::resolve($this->icon_url),
            'earned' => $this->user_badge !== null,
            'earned_at' => $this->user_badge?->earned_at,
        ];
    }
}
