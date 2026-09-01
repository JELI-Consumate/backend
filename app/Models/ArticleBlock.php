<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleBlockType;
use Database\Factories\ArticleBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['article_content_id', 'block_type', 'text_article', 'image_url', 'alt_text', 'order'])]
class ArticleBlock extends Model
{
    /** @use HasFactory<ArticleBlockFactory> */
    use HasFactory, HasUlids, SoftDeletes;

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

    /**
     * Posisi block ini di antara sesama block bertipe list_item dalam satu
     * artikel (1-indexed) — dipakai buat nomor bullet di preview panel
     * admin (lihat ArticleContentPreview), bukan cuma "list_item" ke-N
     * dihitung dari semua block campur tipe lain.
     */
    protected function listItemNumber(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->block_type !== ArticleBlockType::ListItem) {
                return null;
            }

            return static::query()
                ->where('article_content_id', $this->article_content_id)
                ->where('block_type', ArticleBlockType::ListItem->value)
                ->where('order', '<=', $this->order)
                ->count();
        });
    }
}
