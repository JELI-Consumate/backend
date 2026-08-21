<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SimulationAttempt;
use App\Models\SimulationContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationAttempt>
 */
class SimulationAttemptFactory extends Factory
{
    protected $model = SimulationAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'simulation_content_id' => SimulationContent::factory(),
        ];
    }
}
