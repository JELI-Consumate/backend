<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ResourcePagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function resourcePaths(): array
    {
        return [
            'sectors' => ['/admin/sectors'],
            'journeys' => ['/admin/journeys'],
            'modules' => ['/admin/modules'],
            'video-contents' => ['/admin/video-contents'],
            'article-contents' => ['/admin/article-contents'],
            'quiz-contents' => ['/admin/quiz-contents'],
            'simulation-contents' => ['/admin/simulation-contents'],
            'reflection-contents' => ['/admin/reflection-contents'],
            'users' => ['/admin/users'],
            'learning-analytics' => ['/admin/learning-analytics'],
        ];
    }

    #[DataProvider('resourcePaths')]
    public function test_resource_index_page_loads(string $path): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get($path)->assertOk();
    }
}
