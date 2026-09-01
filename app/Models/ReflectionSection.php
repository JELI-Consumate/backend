<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReflectionSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reflection_content_id', 'title', 'instruction', 'order'])]
class ReflectionSection extends Model
{
    /** @use HasFactory<ReflectionSectionFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return BelongsTo<ReflectionContent, $this>
     */
    public function reflectionContent(): BelongsTo
    {
        return $this->belongsTo(ReflectionContent::class);
    }

    /**
     * @return HasMany<ReflectionQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ReflectionQuestion::class)->orderBy('order');
    }
}
