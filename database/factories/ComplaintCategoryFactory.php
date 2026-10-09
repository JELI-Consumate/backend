<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ComplaintCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintCategory>
 */
class ComplaintCategoryFactory extends Factory
{
    protected $model = ComplaintCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'problem' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
