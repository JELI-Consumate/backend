<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleBlockType;
use App\Models\ArticleBlock;
use App\Models\ArticleContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleBlock>
 */
class ArticleBlockFactory extends Factory
{
    protected $model = ArticleBlock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_content_id' => ArticleContent::factory(),
            'block_type' => ArticleBlockType::Paragraph,
            'text_article' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
