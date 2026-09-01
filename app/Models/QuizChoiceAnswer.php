<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizChoiceAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'quiz_choice_option_id', 'is_correct'])]
class QuizChoiceAnswer extends Model
{
    /** @use HasFactory<QuizChoiceAnswerFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * @return BelongsTo<QuizQuestion, $this>
     */
    public function quizQuestion(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class);
    }

    /**
     * @return BelongsTo<QuizChoiceOption, $this>
     */
    public function quizChoiceOption(): BelongsTo
    {
        return $this->belongsTo(QuizChoiceOption::class);
    }
}
