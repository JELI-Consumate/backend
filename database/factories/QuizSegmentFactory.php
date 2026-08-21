<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuizSegmentType;
use App\Models\QuizContent;
use App\Models\QuizSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizSegment>
 */
class QuizSegmentFactory extends Factory
{
    protected $model = QuizSegment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_content_id' => QuizContent::factory(),
            'segment_type' => QuizSegmentType::MultipleChoice,
            'title' => fake()->sentence(3),
            'order' => fake()->numberBetween(1, 5),
        ];
    }

    public function likert(): static
    {
        return $this->state(fn (): array => ['segment_type' => QuizSegmentType::Likert]);
    }
}
