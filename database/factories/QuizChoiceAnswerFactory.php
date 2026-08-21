<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizAttempt;
use App\Models\QuizChoiceAnswer;
use App\Models\QuizChoiceOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizChoiceAnswer>
 */
class QuizChoiceAnswerFactory extends Factory
{
    protected $model = QuizChoiceAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'quiz_question_id' => QuizQuestion::factory(),
            'quiz_choice_option_id' => QuizChoiceOption::factory(),
            'is_correct' => false,
        ];
    }
}
