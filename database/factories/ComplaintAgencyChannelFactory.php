<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ComplaintChannelType;
use App\Models\ComplaintAgency;
use App\Models\ComplaintAgencyChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintAgencyChannel>
 */
class ComplaintAgencyChannelFactory extends Factory
{
    protected $model = ComplaintAgencyChannel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'complaint_agency_id' => ComplaintAgency::factory(),
            'type' => ComplaintChannelType::Website,
            'label' => fake()->words(2, true),
            'value' => fake()->url(),
            'button_label' => null,
            'order' => fake()->numberBetween(1, 100),
        ];
    }
}
