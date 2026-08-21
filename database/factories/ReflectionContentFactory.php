<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReflectionContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReflectionContent>
 */
class ReflectionContentFactory extends Factory
{
    protected $model = ReflectionContent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'opening_message' => fake()->paragraph(),
        ];
    }
}
