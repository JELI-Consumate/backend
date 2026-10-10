<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ComplaintAgency;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk ringkas (kartu beranda). Detail lengkap ada di
 * ComplaintAgencyDetailResource.
 *
 * @mixin ComplaintAgency
 */
class ComplaintAgencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'short_name' => $this->short_name,
            'full_name' => $this->full_name,
            'summary' => $this->summary,
            'logo_url' => MediaUrl::resolve($this->logo_url),
            'order' => $this->order,
        ];
    }
}
