<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SimulationType;
use App\Models\SimulationContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationContent>
 */
class SimulationContentFactory extends Factory
{
    protected $model = SimulationContent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'simulation_type' => SimulationType::Matching,
            'scenario' => fake()->paragraph(),
        ];
    }
}
