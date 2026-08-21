<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ModuleType;
use App\Enums\PublishStatus;
use App\Models\Journey;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journey_id' => Journey::factory(),
            'type' => ModuleType::Materi,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'estimated_minutes' => fake()->numberBetween(3, 20),
            'order' => fake()->numberBetween(1, 100),
            'is_required' => true,
            'status' => PublishStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => PublishStatus::Draft]);
    }
}
