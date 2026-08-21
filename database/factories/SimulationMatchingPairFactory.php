<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SimulationContent;
use App\Models\SimulationMatchingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationMatchingPair>
 */
class SimulationMatchingPairFactory extends Factory
{
    protected $model = SimulationMatchingPair::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simulation_content_id' => SimulationContent::factory(),
            'left_label' => fake()->word(),
            'right_label' => fake()->word(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
