<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LikertScaleOption;
use App\Models\QuizAttempt;
use App\Models\QuizLikertAnswer;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizLikertAnswer>
 */
class QuizLikertAnswerFactory extends Factory
{
    protected $model = QuizLikertAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'quiz_question_id' => QuizQuestion::factory(),
            'likert_scale_option_id' => LikertScaleOption::factory(),
        ];
    }
}
