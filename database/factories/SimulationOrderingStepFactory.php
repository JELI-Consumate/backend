<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SimulationContent;
use App\Models\SimulationOrderingStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationOrderingStep>
 */
class SimulationOrderingStepFactory extends Factory
{
    protected $model = SimulationOrderingStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simulation_content_id' => SimulationContent::factory(),
            'label' => fake()->word(),
            'correct_position' => fake()->numberBetween(1, 10),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
