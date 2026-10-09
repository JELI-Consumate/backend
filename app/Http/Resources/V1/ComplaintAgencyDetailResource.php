<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ComplaintAgency;
use Illuminate\Http\Request;

/**
 * @mixin ComplaintAgency
 */
final class ComplaintAgencyDetailResource extends ComplaintAgencyResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'when_to_contact' => $this->when_to_contact ?? [],
            'how_to_access' => $this->how_to_access,
            'notes' => $this->notes,
            'channels' => ComplaintAgencyChannelResource::collection($this->channels),
        ];
    }
}
