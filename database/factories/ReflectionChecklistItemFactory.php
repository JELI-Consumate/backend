<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReflectionChecklistItem;
use App\Models\ReflectionQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReflectionChecklistItem>
 */
class ReflectionChecklistItemFactory extends Factory
{
    protected $model = ReflectionChecklistItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reflection_question_id' => ReflectionQuestion::factory(),
            'label' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
