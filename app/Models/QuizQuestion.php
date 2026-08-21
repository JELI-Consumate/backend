<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['quiz_segment_id', 'question', 'explanation', 'order'])]
class QuizQuestion extends Model
{
    /** @use HasFactory<QuizQuestionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<QuizSegment, $this>
     */
    public function quizSegment(): BelongsTo
    {
        return $this->belongsTo(QuizSegment::class);
    }

    /**
     * @return HasMany<QuizChoiceOption, $this>
     */
    public function choiceOptions(): HasMany
    {
        return $this->hasMany(QuizChoiceOption::class)->orderBy('order');
    }
}
