<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LikertScaleOption;
use App\Models\QuizSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LikertScaleOption>
 */
class LikertScaleOptionFactory extends Factory
{
    protected $model = LikertScaleOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_segment_id' => QuizSegment::factory(),
            'value' => fake()->numberBetween(1, 5),
            'label' => fake()->word(),
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
