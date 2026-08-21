<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SimulationAttempt;
use App\Models\SimulationMatchingAnswer;
use App\Models\SimulationMatchingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationMatchingAnswer>
 */
class SimulationMatchingAnswerFactory extends Factory
{
    protected $model = SimulationMatchingAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simulation_attempt_id' => SimulationAttempt::factory(),
            'simulation_matching_pair_id' => SimulationMatchingPair::factory(),
            'submitted_right_pair_id' => SimulationMatchingPair::factory(),
            'is_correct' => false,
        ];
    }
}
