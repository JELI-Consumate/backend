<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReflectionChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reflection_question_id', 'label', 'order'])]
class ReflectionChecklistItem extends Model
{
    /** @use HasFactory<ReflectionChecklistItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<ReflectionQuestion, $this>
     */
    public function reflectionQuestion(): BelongsTo
    {
        return $this->belongsTo(ReflectionQuestion::class);
    }

    /**
     * @return HasMany<ReflectionChecklistAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ReflectionChecklistAnswer::class);
    }
}
