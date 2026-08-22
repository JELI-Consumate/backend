<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReflectionChecklistAnswer;
use App\Models\ReflectionChecklistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReflectionChecklistAnswer>
 */
class ReflectionChecklistAnswerFactory extends Factory
{
    protected $model = ReflectionChecklistAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reflection_checklist_item_id' => ReflectionChecklistItem::factory(),
            'is_checked' => true,
        ];
    }
}
