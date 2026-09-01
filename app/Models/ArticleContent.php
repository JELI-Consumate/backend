<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ArticleContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title'])]
class ArticleContent extends Model
{
    /** @use HasFactory<ArticleContentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return MorphOne<ModulePage, $this>
     */
    public function modulePage(): MorphOne
    {
        return $this->morphOne(ModulePage::class, 'contentable');
    }

    /**
     * @return HasMany<ArticleBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(ArticleBlock::class)->orderBy('order');
    }
}
