<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_content_id' => QuizContent::factory(),
            'attempt_number' => 1,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'choice_score' => 8,
            'choice_max_score' => 10,
            'passed' => true,
            'likert_average' => 4.20,
            'completed_at' => now(),
        ]);
    }
}
