<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\QuizKind;
use App\Models\Badge;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\VideoContent;
use App\Services\Gamification\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GamificationTest extends TestCase
{
    use RefreshDatabase;

    private function createPage(Module $module, int $order): ModulePage
    {
        $video = VideoContent::factory()->create();

        return ModulePage::factory()->create([
            'module_id' => $module->id,
            'order' => $order,
            'contentable_type' => 'video',
            'contentable_id' => $video->id,
        ]);
    }

    /**
     * BR-07: badge diberikan otomatis tepat sekali begitu journey selesai.
     */
    public function test_badge_is_awarded_automatically_when_journey_completes(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $badge = Badge::factory()->create(['journey_id' => $journey->id]);

        $module = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => true]);
        $page = $this->createPage($module, 1);

        $this->actingAs($user)->postJson("/api/v1/module-pages/{$page->id}/complete")->assertOk();

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/badges');
        $response->assertOk()
            ->assertJsonPath('data.0.id', $badge->id)
            ->assertJsonPath('data.0.earned', true);
    }

    public function test_journey_without_badge_completes_without_error(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);

        $module = Module::factory()->create(['journey_id' => $journey->id, 'is_required' => true]);
        $page = $this->createPage($module, 1);

        $this->actingAs($user)->postJson("/api/v1/module-pages/{$page->id}/complete")->assertOk();

        $this->assertDatabaseCount('user_badges', 0);
    }

    /**
     * BR-07: race condition aman — dua panggilan hampir bersamaan tidak
     * menghasilkan baris user_badges ganda (unique index sebagai pengaman
     * kedua di luar firstOrCreate).
     */
    public function test_badge_award_stays_unique_even_when_called_twice(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        Badge::factory()->create(['journey_id' => $journey->id]);

        $service = app(BadgeService::class);
        $first = $service->awardForJourney($user, $journey);
        $second = $service->awardForJourney($user, $journey);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, UserBadge::query()->where('user_id', $user->id)->count());
    }

    /**
     * BR-12: 50% pengetahuan (choice) + 50% sikap (likert dinormalisasi 0-100),
     * diambil dari attempt posttest terakhir sektor.
     */
    public function test_empowerment_index_uses_last_posttest_attempt(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();

        $pretest = QuizContent::factory()->create(['kind' => QuizKind::Pretest, 'sector_id' => $sector->id, 'journey_id' => null]);
        QuizAttempt::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $pretest->id,
            'choice_score' => 2,
            'choice_max_score' => 10,
            'likert_average' => 1.00,
        ]);

        $posttest = QuizContent::factory()->create(['kind' => QuizKind::Posttest, 'sector_id' => $sector->id, 'journey_id' => null]);
        QuizAttempt::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $posttest->id,
            'choice_score' => 8,
            'choice_max_score' => 10,
            'likert_average' => 4.20,
        ]);

        // knowledge = 80, attitude = (4.20-1)/(5-1)*100 = 80 -> index 50:50 = 80.
        $response = $this->actingAs($user)->getJson('/api/v1/empowerment-index');

        $response->assertOk()
            ->assertJsonPath('data.sectors.0.sector_id', $sector->id)
            ->assertJsonPath('data.sectors.0.empowerment_index', 80)
            ->assertJsonPath('data.aggregate', 80);
    }

    public function test_empowerment_index_falls_back_to_pretest_when_no_posttest(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();

        $pretest = QuizContent::factory()->create(['kind' => QuizKind::Pretest, 'sector_id' => $sector->id, 'journey_id' => null]);
        QuizAttempt::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $pretest->id,
            'choice_score' => 4,
            'choice_max_score' => 10,
            'likert_average' => 3.00,
        ]);

        // knowledge = 40, attitude = (3-1)/4*100 = 50 -> index = (40+50)/2 = 45.
        $response = $this->actingAs($user)->getJson('/api/v1/empowerment-index');

        $response->assertOk()->assertJsonPath('data.sectors.0.empowerment_index', 45);
    }

    public function test_empowerment_index_is_zero_when_no_attempt_yet(): void
    {
        $user = User::factory()->create();
        Sector::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/empowerment-index');

        $response->assertOk()->assertJsonPath('data.sectors.0.empowerment_index', 0);
    }
}
