<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VideoContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoContent>
 */
class VideoContentFactory extends Factory
{
    protected $model = VideoContent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'prompt_question' => fake()->sentence(),
        ];
    }
}
