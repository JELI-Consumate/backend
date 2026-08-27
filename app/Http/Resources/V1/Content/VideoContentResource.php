<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Content;

use App\Models\VideoContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VideoContent
 */
final class VideoContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'youtube_url' => $this->youtube_url,
            'youtube_video_id' => $this->youtube_video_id,
            'prompt_question' => $this->prompt_question,
        ];
    }
}
