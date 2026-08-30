<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Journey;
use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `GET /journeys/{id}` -> `data.quiz_score`, dipakai kartu "Ringkasan
 * Journey" di layar perayaan begitu journey selesai (lihat
 * JourneyController::attachQuizScore).
 */
final class JourneyQuizScoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeJourney(): Journey
    {
        $sector = Sector::factory()->create();

        return Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
    }

    public function test_quiz_score_is_null_when_journey_has_no_quiz_module(): void
    {
        $user = User::factory()->create();
        $journey = $this->makeJourney();

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}");

        $response->assertOk()->assertJsonPath('data.quiz_score', null);
    }

    public function test_quiz_score_is_null_when_quiz_not_attempted_yet(): void
    {
        $user = User::factory()->create();
        $journey = $this->makeJourney();
        QuizContent::factory()->create(['journey_id' => $journey->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}");

        $response->assertOk()->assertJsonPath('data.quiz_score', null);
    }

    public function test_quiz_score_is_percentage_of_last_completed_attempt(): void
    {
        $user = User::factory()->create();
        $journey = $this->makeJourney();
        $quiz = QuizContent::factory()->create(['journey_id' => $journey->id]);

        QuizAttempt::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $quiz->id,
            'attempt_number' => 1,
            'choice_score' => 6,
            'choice_max_score' => 10,
        ]);
        // Attempt kedua (terakhir) yang menentukan skor -- bukan yang pertama,
        // bukan pula rata-rata / yang terbaik.
        QuizAttempt::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $quiz->id,
            'attempt_number' => 2,
            'choice_score' => 10,
            'choice_max_score' => 10,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}");

        $response->assertOk()->assertJsonPath('data.quiz_score', 100);
    }

    public function test_quiz_score_ignores_other_users_attempts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $journey = $this->makeJourney();
        $quiz = QuizContent::factory()->create(['journey_id' => $journey->id]);

        QuizAttempt::factory()->completed()->create([
            'user_id' => $otherUser->id,
            'quiz_content_id' => $quiz->id,
            'choice_score' => 10,
            'choice_max_score' => 10,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/journeys/{$journey->id}");

        $response->assertOk()->assertJsonPath('data.quiz_score', null);
    }
}
