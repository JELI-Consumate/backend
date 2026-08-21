<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuizKind;
use App\Models\Journey;
use App\Models\QuizContent;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizContent>
 */
class QuizContentFactory extends Factory
{
    protected $model = QuizContent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => QuizKind::Quiz,
            'journey_id' => Journey::factory(),
            'sector_id' => null,
            'passing_score' => 70,
            'shuffle_questions' => false,
        ];
    }

    public function pretest(): static
    {
        return $this->state(fn (): array => [
            'kind' => QuizKind::Pretest,
            'journey_id' => null,
            'sector_id' => Sector::factory(),
        ]);
    }

    public function posttest(): static
    {
        return $this->state(fn (): array => [
            'kind' => QuizKind::Posttest,
            'journey_id' => null,
            'sector_id' => Sector::factory(),
        ]);
    }
}
