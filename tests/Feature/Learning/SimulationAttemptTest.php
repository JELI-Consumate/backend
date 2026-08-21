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

    public function test_submitting_matching_simulation_scores_and_completes_module_page(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, $page, $pairs] = $this->createMatchingSimulation($journey, 2);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $payload = [
            'matching_answers' => [
                ['simulation_matching_pair_id' => $pairs[0]->id, 'submitted_right_pair_id' => $pairs[0]->id],
                ['simulation_matching_pair_id' => $pairs[1]->id, 'submitted_right_pair_id' => $pairs[0]->id],
            ],
            'ordering_answers' => [],
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/submit", $payload);

        $response->assertOk()
            ->assertJsonPath('data.score', 1)
            ->assertJsonPath('data.max_score', 2)
            ->assertJsonPath('data.is_passed', false)
            ->assertJsonPath('data.matching_review.0.is_correct', true)
            ->assertJsonPath('data.matching_review.1.is_correct', false);

        $pageResponse = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");
        $pageResponse->assertOk()->assertJsonPath('data.progress.status', 'completed');
    }

    public function test_submitting_ordering_simulation_scores_against_correct_position(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, , $steps] = $this->createOrderingSimulation($journey, 3);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $payload = [
            'matching_answers' => [],
            'ordering_answers' => [
                ['simulation_ordering_step_id' => $steps[0]->id, 'submitted_position' => 1],
                ['simulation_ordering_step_id' => $steps[1]->id, 'submitted_position' => 3],
                ['simulation_ordering_step_id' => $steps[2]->id, 'submitted_position' => 3],
            ],
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/submit", $payload);

        $response->assertOk()
            ->assertJsonPath('data.score', 2)
            ->assertJsonPath('data.max_score', 3)
            ->assertJsonPath('data.ordering_review.0.is_correct', true)
            ->assertJsonPath('data.ordering_review.1.is_correct', false)
            ->assertJsonPath('data.ordering_review.2.is_correct', true);
    }

    /**
     * BR-08: attempt yang sudah completed_at != null bersifat immutable.
     */
    public function test_resubmitting_completed_simulation_attempt_returns_409(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation, , $pairs] = $this->createMatchingSimulation($journey, 1);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $payload = ['matching_answers' => [
            ['simulation_matching_pair_id' => $pairs[0]->id, 'submitted_right_pair_id' => $pairs[0]->id],
        ], 'ordering_answers' => []];

        $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/submit", $payload)->assertOk();

        $response = $this->actingAs($user)->postJson("/api/v1/simulation-attempts/{$attemptId}/submit", $payload);

        $response->assertStatus(409)->assertJsonPath('code', 'ATTEMPT_ALREADY_COMPLETED');
    }

    public function test_other_user_cannot_submit_someone_elses_simulation_attempt(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$simulation] = $this->createMatchingSimulation($journey, 1);

        $attemptId = $this->actingAs($owner)->postJson("/api/v1/simulations/{$simulation->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $this->actingAs($intruder)->postJson("/api/v1/simulation-attempts/{$attemptId}/submit", [
            'matching_answers' => [], 'ordering_answers' => [],
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
