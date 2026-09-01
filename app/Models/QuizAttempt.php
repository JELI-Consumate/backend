<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'quiz_content_id', 'attempt_number', 'choice_score', 'choice_max_score', 'passed', 'likert_average', 'completed_at'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'likert_average' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<QuizContent, $this>
     */
    public function quizContent(): BelongsTo
    {
        return $this->belongsTo(QuizContent::class);
    }

    /**
     * @return HasMany<QuizChoiceAnswer, $this>
     */
    public function choiceAnswers(): HasMany
    {
        return $this->hasMany(QuizChoiceAnswer::class);
    }

    /**
     * @return HasMany<QuizLikertAnswer, $this>
     */
    public function likertAnswers(): HasMany
    {
        return $this->hasMany(QuizLikertAnswer::class);
    }
}
