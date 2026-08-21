<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReflectionEntry;
use App\Models\ReflectionQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReflectionEntry>
 */
class ReflectionEntryFactory extends Factory
{
    protected $model = ReflectionEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reflection_question_id' => ReflectionQuestion::factory(),
            'answer_text' => fake()->paragraph(),
        ];
    }
}
