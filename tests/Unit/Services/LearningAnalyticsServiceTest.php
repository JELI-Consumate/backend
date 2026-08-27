<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\QuizKind;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LearningAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_users_count_only_counts_distinct_users_within_the_window(): void
    {
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);
        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $pageOne = ModulePage::factory()->create(['module_id' => $module->id]);
        $pageTwo = ModulePage::factory()->create(['module_id' => $module->id]);

        $recentUser = User::factory()->create();
        $staleUser = User::factory()->create();

        // recentUser progres di dua halaman berbeda dalam window — tetap
        // dihitung satu user, bukan dua.
        ModuleProgress::factory()->create(['user_id' => $recentUser->id, 'module_page_id' => $pageOne->id, 'updated_at' => now()->subDays(5)]);
        ModuleProgress::factory()->create(['user_id' => $recentUser->id, 'module_page_id' => $pageTwo->id, 'updated_at' => now()->subDays(2)]);
        ModuleProgress::factory()->create(['user_id' => $staleUser->id, 'module_page_id' => $pageOne->id, 'updated_at' => now()->subDays(45)]);

        $service = app(LearningAnalyticsService::class);

        $this->assertSame(1, $service->activeUsersCount(null));
    }

    public function test_active_users_count_is_scoped_to_sector(): void
    {
        $sectorA = Sector::factory()->create();
        $sectorB = Sector::factory()->create();
        $pageA = ModulePage::factory()->create(['module_id' => Module::factory()->create(['journey_id' => Journey::factory()->create(['sector_id' => $sectorA->id])->id])->id]);
        $pageB = ModulePage::factory()->create(['module_id' => Module::factory()->create(['journey_id' => Journey::factory()->create(['sector_id' => $sectorB->id])->id])->id]);

        ModuleProgress::factory()->create(['module_page_id' => $pageA->id, 'updated_at' => now()]);
        ModuleProgress::factory()->create(['module_page_id' => $pageB->id, 'updated_at' => now()]);

        $service = app(LearningAnalyticsService::class);

        $this->assertSame(1, $service->activeUsersCount($sectorA->id));
        $this->assertSame(2, $service->activeUsersCount(null));
    }

    public function test_average_quiz_score_is_null_when_no_completed_attempts(): void
    {
        $service = app(LearningAnalyticsService::class);

        $this->assertNull($service->averageQuizScore(null));
    }

    public function test_average_quiz_score_averages_only_completed_attempts(): void
    {
        $quiz = QuizContent::factory()->create();

        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quiz->id, 'choice_score' => 9, 'choice_max_score' => 10]);
        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quiz->id, 'choice_score' => 5, 'choice_max_score' => 10]);
        // Belum selesai — tidak boleh ikut dirata-rata.
        QuizAttempt::factory()->create(['quiz_content_id' => $quiz->id, 'choice_score' => null, 'choice_max_score' => null, 'completed_at' => null]);

        $average = app(LearningAnalyticsService::class)->averageQuizScore(null);

        $this->assertSame(70.0, $average);
    }

    public function test_average_quiz_score_is_scoped_to_sector_via_journey(): void
    {
        $sectorA = Sector::factory()->create();
        $sectorB = Sector::factory()->create();
        $journeyA = Journey::factory()->create(['sector_id' => $sectorA->id]);
        $journeyB = Journey::factory()->create(['sector_id' => $sectorB->id]);
        $quizA = QuizContent::factory()->create(['kind' => QuizKind::Quiz, 'journey_id' => $journeyA->id]);
        $quizB = QuizContent::factory()->create(['kind' => QuizKind::Quiz, 'journey_id' => $journeyB->id]);

        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quizA->id, 'choice_score' => 10, 'choice_max_score' => 10]);
        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quizB->id, 'choice_score' => 0, 'choice_max_score' => 10]);

        $this->assertSame(100.0, app(LearningAnalyticsService::class)->averageQuizScore($sectorA->id));
        $this->assertSame(50.0, app(LearningAnalyticsService::class)->averageQuizScore(null));
    }

    public function test_quiz_pass_rate_is_null_when_no_completed_attempts(): void
    {
        $this->assertNull(app(LearningAnalyticsService::class)->quizPassRate(null));
    }

    public function test_quiz_pass_rate_computes_percentage_of_passed_attempts(): void
    {
        $quiz = QuizContent::factory()->create();

        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quiz->id, 'passed' => true]);
        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quiz->id, 'passed' => true]);
        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quiz->id, 'passed' => false]);
        QuizAttempt::factory()->completed()->create(['quiz_content_id' => $quiz->id, 'passed' => false]);

        $this->assertSame(50.0, app(LearningAnalyticsService::class)->quizPassRate(null));
    }

    public function test_journey_completion_is_ordered_by_sector_order_then_journey_order(): void
    {
        $sectorA = Sector::factory()->create(['order' => 1]);
        $sectorB = Sector::factory()->create(['order' => 2]);

        $journeyB1 = Journey::factory()->create(['sector_id' => $sectorB->id, 'order' => 1, 'title' => 'Journey B1']);
        $journeyA2 = Journey::factory()->create(['sector_id' => $sectorA->id, 'order' => 2, 'title' => 'Journey A2']);
        $journeyA1 = Journey::factory()->create(['sector_id' => $sectorA->id, 'order' => 1, 'title' => 'Journey A1']);

        $titles = array_column(app(LearningAnalyticsService::class)->journeyCompletion(null), 'title');

        $this->assertSame([$journeyA1->title, $journeyA2->title, $journeyB1->title], $titles);
    }

    public function test_journey_completion_computes_percent_and_is_scoped_to_sector(): void
    {
        $sector = Sector::factory()->create();
        $otherSector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id]);
        Journey::factory()->create(['sector_id' => $otherSector->id, 'title' => 'Journey Lain']);

        JourneyProgress::factory()->completed()->create(['journey_id' => $journey->id]);
        JourneyProgress::factory()->completed()->create(['journey_id' => $journey->id]);
        JourneyProgress::factory()->create(['journey_id' => $journey->id]);

        $rows = app(LearningAnalyticsService::class)->journeyCompletion($sector->id);

        $this->assertCount(1, $rows);
        $this->assertSame($journey->title, $rows[0]['title']);
        $this->assertSame($sector->name, $rows[0]['sector']);
        $this->assertSame(3, $rows[0]['total']);
        $this->assertSame(2, $rows[0]['completed']);
        $this->assertSame(67, $rows[0]['percent']);
    }

    public function test_empowerment_index_distribution_is_empty_when_no_sector_progress(): void
    {
        $distribution = app(LearningAnalyticsService::class)->empowermentIndexDistribution(null);

        $this->assertSame(['0-25' => 0, '25-50' => 0, '50-75' => 0, '75-100' => 0], $distribution);
    }

    public function test_empowerment_index_distribution_buckets_users_by_score(): void
    {
        $sector = Sector::factory()->create();
        $posttest = QuizContent::factory()->posttest()->create(['sector_id' => $sector->id]);

        $highScorer = User::factory()->create();
        SectorProgress::factory()->create(['user_id' => $highScorer->id, 'sector_id' => $sector->id]);
        QuizAttempt::factory()->create([
            'user_id' => $highScorer->id,
            'quiz_content_id' => $posttest->id,
            'choice_score' => 10,
            'choice_max_score' => 10,
            'likert_average' => 5.0,
            'completed_at' => now(),
        ]);

        $lowScorer = User::factory()->create();
        SectorProgress::factory()->create(['user_id' => $lowScorer->id, 'sector_id' => $sector->id]);
        // Belum ada attempt sama sekali -> index 0.

        $distribution = app(LearningAnalyticsService::class)->empowermentIndexDistribution($sector->id);

        $this->assertSame(1, $distribution['75-100']);
        $this->assertSame(1, $distribution['0-25']);
        $this->assertSame(0, $distribution['25-50']);
        $this->assertSame(0, $distribution['50-75']);
    }
}
