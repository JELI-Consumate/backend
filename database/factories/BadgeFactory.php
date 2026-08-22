<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Badge;
use App\Models\Journey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journey_id' => Journey::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'icon_url' => fake()->imageUrl(),
        ];
    }
}
