<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ComplaintCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ComplaintCategory
 */
final class ComplaintCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'problem' => $this->problem,
            'agencies' => $this->agencies->map(fn ($agency): array => [
                'slug' => $agency->slug,
                'short_name' => $agency->short_name,
            ])->values(),
        ];
    }
}
