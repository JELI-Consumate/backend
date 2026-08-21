<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReflectionQuestionType;
use App\Models\ReflectionQuestion;
use App\Models\ReflectionSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReflectionQuestion>
 */
class ReflectionQuestionFactory extends Factory
{
    protected $model = ReflectionQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reflection_section_id' => ReflectionSection::factory(),
            'question_type' => ReflectionQuestionType::OpenQuestion,
            'question_text' => fake()->sentence().'?',
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
