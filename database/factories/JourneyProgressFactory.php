<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JourneyProgress>
 */
class JourneyProgressFactory extends Factory
{
    protected $model = JourneyProgress::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'journey_id' => Journey::factory(),
            'status' => ProgressStatus::NotStarted,
            'progress_percent' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ProgressStatus::Completed,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);
    }
}
