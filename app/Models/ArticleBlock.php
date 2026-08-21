<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleBlockType;
use Database\Factories\ArticleBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['article_content_id', 'block_type', 'text_article', 'image_url', 'alt_text', 'order'])]
class ArticleBlock extends Model
{
    /** @use HasFactory<ArticleBlockFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'block_type' => ArticleBlockType::class,
        ];
    }

    /**
     * @return BelongsTo<ArticleContent, $this>
     */
    public function articleContent(): BelongsTo
    {
        return $this->belongsTo(ArticleContent::class);
    }
}
