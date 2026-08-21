<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use App\Models\ModulePage;
use App\Models\VideoContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModulePage>
 */
class ModulePageFactory extends Factory
{
    protected $model = ModulePage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'order' => fake()->numberBetween(1, 20),
            'contentable_type' => 'video',
            'contentable_id' => VideoContent::factory(),
        ];
    }
}
