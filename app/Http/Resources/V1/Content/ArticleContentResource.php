<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Content;

use App\Models\ArticleContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ArticleContent
 */
final class ArticleContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'blocks' => $this->whenLoaded('blocks', fn () => $this->blocks->map(fn ($block): array => [
                'id' => $block->id,
                'block_type' => $block->block_type->value,
                'text_article' => $block->text_article,
                'image_url' => $block->image_url,
                'alt_text' => $block->alt_text,
                'order' => $block->order,
            ])),
        ];
    }
}
