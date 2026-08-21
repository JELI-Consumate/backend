<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReflectionContent;
use App\Models\ReflectionSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReflectionSection>
 */
class ReflectionSectionFactory extends Factory
{
    protected $model = ReflectionSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reflection_content_id' => ReflectionContent::factory(),
            'title' => fake()->sentence(3),
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
