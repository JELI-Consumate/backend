<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizChoiceOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizChoiceOption>
 */
class QuizChoiceOptionFactory extends Factory
{
    protected $model = QuizChoiceOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_question_id' => QuizQuestion::factory(),
            'option_text' => fake()->sentence(),
            'is_correct' => false,
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
