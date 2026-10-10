<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ComplaintAgency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintAgency>
 */
class ComplaintAgencyFactory extends Factory
{
    protected $model = ComplaintAgency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'short_name' => strtoupper(fake()->unique()->lexify('????')),
            'full_name' => fake()->words(4, true),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'when_to_contact' => [fake()->sentence(), fake()->sentence()],
            'how_to_access' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
