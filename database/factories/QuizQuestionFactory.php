<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizQuestion;
use App\Models\QuizSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_segment_id' => QuizSegment::factory(),
            'question' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
