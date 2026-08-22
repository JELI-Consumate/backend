<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\Sector;
use App\Models\User;
use App\Services\Gamification\EmpowermentIndexService;
use App\Support\CacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class EmpowermentIndexServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_returns_zero_when_no_pretest_or_posttest_attempt_exists(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();

        $index = app(EmpowermentIndexService::class)->calculate($user, $sector);

        $this->assertSame(0, $index);
    }

    /**
     * BR-12: 50% skor pengetahuan (choice benar) + 50% skor sikap (likert 1-5
     * dinormalisasi ke 0-100), config/learning.php bobot 50:50.
     */
    public function test_calculate_combines_knowledge_and_attitude_with_configured_weights(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $posttest = QuizContent::factory()->posttest()->create(['sector_id' => $sector->id]);

        // Pengetahuan: 8/10 = 80. Sikap: likert 5 (max) -> normalisasi (5-1)/(5-1)*100 = 100.
        // Index = (50*80 + 50*100)/100 = 90.
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $posttest->id,
            'choice_score' => 8,
            'choice_max_score' => 10,
            'likert_average' => 5.0,
            'completed_at' => now(),
        ]);

        $index = app(EmpowermentIndexService::class)->calculate($user, $sector);

        $this->assertSame(90, $index);
    }

    public function test_calculate_falls_back_to_pretest_when_posttest_missing(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $pretest = QuizContent::factory()->pretest()->create(['sector_id' => $sector->id]);

        // Pengetahuan: 5/10 = 50. Sikap: likert 3 -> (3-1)/(5-1)*100 = 50. Index = 50.
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $pretest->id,
            'choice_score' => 5,
            'choice_max_score' => 10,
            'likert_average' => 3.0,
            'completed_at' => now(),
        ]);

        $index = app(EmpowermentIndexService::class)->calculate($user, $sector);

        $this->assertSame(50, $index);
    }

    public function test_calculate_prefers_posttest_over_pretest_when_both_exist(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $pretest = QuizContent::factory()->pretest()->create(['sector_id' => $sector->id]);
        $posttest = QuizContent::factory()->posttest()->create(['sector_id' => $sector->id]);

        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $pretest->id,
            'choice_score' => 1,
            'choice_max_score' => 10,
            'likert_average' => 1.0,
            'completed_at' => now(),
        ]);
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $posttest->id,
            'choice_score' => 10,
            'choice_max_score' => 10,
            'likert_average' => 5.0,
            'completed_at' => now(),
        ]);

        $index = app(EmpowermentIndexService::class)->calculate($user, $sector);

        // Kalau salah ambil pretest, hasilnya bakal jauh lebih rendah (~0), bukan 100.
        $this->assertSame(100, $index);
    }

    public function test_calculate_ignores_uncompleted_attempts(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $posttest = QuizContent::factory()->posttest()->create(['sector_id' => $sector->id]);

        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $posttest->id,
            'choice_score' => null,
            'choice_max_score' => null,
            'likert_average' => null,
            'completed_at' => null,
        ]);

        $index = app(EmpowermentIndexService::class)->calculate($user, $sector);

        $this->assertSame(0, $index);
    }

    public function test_calculate_is_cached_and_not_recomputed_on_second_call(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $posttest = QuizContent::factory()->posttest()->create(['sector_id' => $sector->id]);

        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $posttest->id,
            'choice_score' => 10,
            'choice_max_score' => 10,
            'likert_average' => 5.0,
            'completed_at' => now(),
        ]);

        $service = app(EmpowermentIndexService::class);
        $first = $service->calculate($user, $sector);

        // Ubah data sumber tanpa invalidasi cache — hasil kedua harus tetap sama (dari cache).
        QuizAttempt::query()->where('user_id', $user->id)->update(['choice_score' => 0]);

        $second = $service->calculate($user, $sector);

        $this->assertSame($first, $second);
        $this->assertTrue(Cache::has(CacheKey::empowermentIndex($user->id, $sector->id)));
    }
}
