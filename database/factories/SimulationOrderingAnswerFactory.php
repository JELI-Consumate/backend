<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SimulationAttempt;
use App\Models\SimulationOrderingAnswer;
use App\Models\SimulationOrderingStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationOrderingAnswer>
 */
class SimulationOrderingAnswerFactory extends Factory
{
    protected $model = SimulationOrderingAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simulation_attempt_id' => SimulationAttempt::factory(),
            'simulation_ordering_step_id' => SimulationOrderingStep::factory(),
            'submitted_position' => 1,
            'is_correct' => false,
        ];
    }
}
