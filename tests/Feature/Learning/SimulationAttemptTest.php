<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\SimulationType;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\Sector;
use App\Models\SimulationContent;
use App\Models\SimulationMatchingPair;
use App\Models\SimulationOrderingStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SimulationAttemptTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: SimulationContent, 1: ModulePage, 2: list<SimulationMatchingPair>}
     */
    private function createMatchingSimulation(Journey $journey, int $pairCount = 2): array
    {
        $simulation = SimulationContent::factory()->create(['simulation_type' => SimulationType::Matching]);

        $pairs = [];
        foreach (range(1, $pairCount) as $order) {
            $pairs[] = SimulationMatchingPair::factory()->create([
                'simulation_content_id' => $simulation->id,
                'order' => $order,
            ]);
        }

        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'simulation',
            'contentable_id' => $simulation->id,
        ]);

        return [$simulation, $page, $pairs];
    }

    /**
     * @return array{0: SimulationContent, 1: ModulePage, 2: list<SimulationOrderingStep>}
     */
    private function createOrderingSimulation(Journey $journey, int $stepCount = 3): array
    {
        $simulation = SimulationContent::factory()->create(['simulation_type' => SimulationType::Ordering]);

        $steps = [];
        foreach (range(1, $stepCount) as $order) {
            $steps[] = SimulationOrderingStep::factory()->create([
                'simulation_content_id' => $simulation->id,
                'order' => $order,
                'correct_position' => $order,
            ]);
        }

        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'simulation',
            'contentable_id' => $simulation->id,
        ]);

        return [$simulation, $page, $steps];
    }

    public function test_simulation_show_does_not_leak_correct_position(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation] = $this->createOrderingSimulation($journey);

        $response = $this->actingAs($user)->getJson("/api/v1/simulations/{$simulation->id}");

        $response->assertOk();
        $this->assertStringNotContainsString('correct_position', (string) json_encode($response->json()));
    }

    public function test_wrong_answer_is_rejected_and_not_persisted(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, , $pairs] = $this->createMatchingSimulation($journey, 2);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $response = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'matching',
            'simulation_matching_pair_id' => $pairs[1]->id,
            'submitted_right_pair_id' => $pairs[0]->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.correct', false)
            ->assertJsonPath('data.attempt.completed_at', null);

        $this->assertDatabaseCount('simulation_matching_answers', 0);
    }

    public function test_correct_matching_answer_is_accepted_and_saved(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, , $pairs] = $this->createMatchingSimulation($journey, 2);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $response = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'matching',
            'simulation_matching_pair_id' => $pairs[0]->id,
            'submitted_right_pair_id' => $pairs[0]->id,
        ]);

        $response->assertOk()->assertJsonPath('data.correct', true);
        $this->assertDatabaseHas('simulation_matching_answers', [
            'simulation_attempt_id' => $attemptId,
            'simulation_matching_pair_id' => $pairs[0]->id,
            'is_correct' => 1,
        ]);
    }

    public function test_attempt_completes_and_marks_module_page_once_all_items_answered_correctly(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, $page, $pairs] = $this->createMatchingSimulation($journey, 2);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        // Coba salah dulu untuk pair kedua — harus ditolak, attempt belum selesai.
        $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'matching',
            'simulation_matching_pair_id' => $pairs[1]->id,
            'submitted_right_pair_id' => $pairs[0]->id,
        ])->assertJsonPath('data.correct', false);

        $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'matching',
            'simulation_matching_pair_id' => $pairs[0]->id,
            'submitted_right_pair_id' => $pairs[0]->id,
        ])->assertJsonPath('data.correct', true)
            ->assertJsonPath('data.attempt.completed_at', null);

        $final = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'matching',
            'simulation_matching_pair_id' => $pairs[1]->id,
            'submitted_right_pair_id' => $pairs[1]->id,
        ]);

        $final->assertOk()
            ->assertJsonPath('data.correct', true)
            ->assertJsonPath('data.attempt.score', 2)
            ->assertJsonPath('data.attempt.max_score', 2)
            ->assertJsonPath('data.attempt.is_passed', true);
        $this->assertNotNull($final->json('data.attempt.completed_at'));

        $pageResponse = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");
        $pageResponse->assertOk()->assertJsonPath('data.progress.status', 'completed');
    }

    public function test_ordering_answer_checked_against_correct_position(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, , $steps] = $this->createOrderingSimulation($journey, 3);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'ordering',
            'simulation_ordering_step_id' => $steps[1]->id,
            'submitted_position' => 3,
        ])->assertJsonPath('data.correct', false);

        $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'ordering',
            'simulation_ordering_step_id' => $steps[1]->id,
            'submitted_position' => 2,
        ])->assertJsonPath('data.correct', true);

        $this->assertDatabaseCount('simulation_ordering_answers', 1);
    }

    /**
     * BR-08: attempt yang sudah completed_at != null bersifat immutable.
     */
    public function test_checking_answer_on_completed_attempt_returns_409(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, , $pairs] = $this->createMatchingSimulation($journey, 1);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $payload = [
            'type' => 'matching',
            'simulation_matching_pair_id' => $pairs[0]->id,
            'submitted_right_pair_id' => $pairs[0]->id,
        ];

        $first = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", $payload);
        $this->assertNotNull($first->json('data.attempt.completed_at'));

        $response = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", $payload);

        $response->assertStatus(409)->assertJsonPath('code', 'ATTEMPT_ALREADY_COMPLETED');
    }

    public function test_other_user_cannot_check_someone_elses_simulation_attempt(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, , $pairs] = $this->createMatchingSimulation($journey, 1);

        $attemptId = $this->actingAs($owner)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $this->actingAs($intruder)->postJson("/api/v1/simulation-attempts/{$attemptId}/check", [
            'type' => 'matching',
            'simulation_matching_pair_id' => $pairs[0]->id,
            'submitted_right_pair_id' => $pairs[0]->id,
        ])->assertForbidden();
    }

    public function test_cannot_start_simulation_attempt_in_locked_journey(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $lockedJourney = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 2]);
        [$simulation] = $this->createMatchingSimulation($lockedJourney, 1);

        $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertStatus(403)->assertJsonPath('code', 'JOURNEY_LOCKED');
    }
}
