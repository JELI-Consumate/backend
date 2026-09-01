<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReflectionContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'opening_message', 'closing_title', 'closing_message'])]
class ReflectionContent extends Model
{
    /** @use HasFactory<ReflectionContentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return MorphOne<ModulePage, $this>
     */
    public function modulePage(): MorphOne
    {
        return $this->morphOne(ModulePage::class, 'contentable');
    }

    /**
     * @return HasMany<ReflectionSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ReflectionSection::class)->orderBy('order');
    }
}
