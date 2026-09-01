<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizLikertAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'likert_scale_option_id'])]
class QuizLikertAnswer extends Model
{
    /** @use HasFactory<QuizLikertAnswerFactory> */
    use HasFactory, HasUlids;

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
     * @return BelongsTo<LikertScaleOption, $this>
     */
    public function likertScaleOption(): BelongsTo
    {
        return $this->belongsTo(LikertScaleOption::class);
    }
}
