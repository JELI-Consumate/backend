<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ComplaintAgencyChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ComplaintAgencyChannel
 */
final class ComplaintAgencyChannelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'label' => $this->label,
            'value' => $this->value,
            'url' => $this->url(),
            'button_label' => $this->button_label,
        ];
    }
}
