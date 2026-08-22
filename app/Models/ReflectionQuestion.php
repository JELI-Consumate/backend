<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReflectionQuestionType;
use Database\Factories\ReflectionQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reflection_section_id', 'question_type', 'question_text', 'order'])]
class ReflectionQuestion extends Model
{
    /** @use HasFactory<ReflectionQuestionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'question_type' => ReflectionQuestionType::class,
        ];
    }

    /**
     * @return BelongsTo<ReflectionSection, $this>
     */
    public function reflectionSection(): BelongsTo
    {
        return $this->belongsTo(ReflectionSection::class);
    }

    /**
     * Cuma relevan untuk question_type=checklist.
     *
     * @return HasMany<ReflectionChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ReflectionChecklistItem::class)->orderBy('order');
    }
}
