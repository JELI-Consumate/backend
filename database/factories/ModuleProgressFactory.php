<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProgressStatus;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleProgress>
 */
class ModuleProgressFactory extends Factory
{
    protected $model = ModuleProgress::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'module_page_id' => ModulePage::factory(),
            'status' => ProgressStatus::NotStarted,
            'last_position' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ProgressStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
