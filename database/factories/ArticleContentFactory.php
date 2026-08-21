<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ArticleContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleContent>
 */
class ArticleContentFactory extends Factory
{
    protected $model = ArticleContent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
        ];
    }
}
