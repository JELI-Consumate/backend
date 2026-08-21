<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Data\QuizSubmissionData;
use App\Enums\ProgressStatus;
use App\Enums\QuizKind;
use App\Enums\QuizSegmentType;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizChoiceOption;
use App\Models\QuizContent;
use App\Models\QuizQuestion;
use App\Models\QuizSegment;
use App\Models\Sector;
use App\Models\User;
use App\Services\Quiz\QuizAttemptService;
use App\Services\Quiz\QuizScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: QuizContent, 1: ModulePage, 2: list<QuizQuestion>}
     */
    private function createJourneyQuiz(Journey $journey, int $questionCount = 2): array
    {
        $quiz = QuizContent::factory()->create(['journey_id' => $journey->id]);
        $segment = QuizSegment::factory()->create(['quiz_content_id' => $quiz->id, 'segment_type' => QuizSegmentType::MultipleChoice]);

        $questions = [];

        foreach (range(1, $questionCount) as $order) {
            $question = QuizQuestion::factory()->create(['quiz_segment_id' => $segment->id, 'order' => $order]);
            QuizChoiceOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => true, 'order' => 1]);
            QuizChoiceOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false, 'order' => 2]);
            $questions[] = $question->fresh();
        }

        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'quiz',
            'contentable_id' => $quiz->id,
        ]);

        return [$quiz, $page, $questions];
    }

    /**
     * @return array{0: QuizContent, 1: list<QuizQuestion>}
     */
    private function createSectorQuiz(Sector $sector, QuizKind $kind): array
    {
        $quiz = QuizContent::factory()->create(['kind' => $kind, 'sector_id' => $sector->id, 'journey_id' => null]);

        $choiceSegment = QuizSegment::factory()->create(['quiz_content_id' => $quiz->id, 'segment_type' => QuizSegmentType::MultipleChoice, 'order' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_segment_id' => $choiceSegment->id]);
        QuizChoiceOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => true]);
        QuizChoiceOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);

        QuizSegment::factory()->likert()->create(['quiz_content_id' => $quiz->id, 'order' => 2]);

        return [$quiz->fresh(), [$question->fresh()]];
    }

    private function correctOptionIdFor(QuizQuestion $question): int
    {
        return QuizChoiceOption::query()->where('quiz_question_id', $question->id)->where('is_correct', true)->value('id');
    }

    private function wrongOptionIdFor(QuizQuestion $question): int
    {
        return QuizChoiceOption::query()->where('quiz_question_id', $question->id)->where('is_correct', false)->value('id');
    }

    public function test_quiz_show_does_not_leak_is_correct(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$quiz] = $this->createJourneyQuiz($journey);

        $response = $this->actingAs($user)->getJson("/api/v1/quizzes/{$quiz->id}");

        $response->assertOk();
        $this->assertStringNotContainsString('is_correct', (string) json_encode($response->json()));
    }

    public function test_submitting_quiz_scores_choice_answers_and_completes_module_page(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$quiz, $page, $questions] = $this->createJourneyQuiz($journey, 2);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $payload = [
            'choice_answers' => [
                ['quiz_question_id' => $questions[0]->id, 'quiz_choice_option_id' => $this->correctOptionIdFor($questions[0])],
                ['quiz_question_id' => $questions[1]->id, 'quiz_choice_option_id' => $this->wrongOptionIdFor($questions[1])],
            ],
            'likert_answers' => [],
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/quiz-attempts/{$attemptId}/submit", $payload);

        $response->assertOk()
            ->assertJsonPath('data.choice_score', 1)
            ->assertJsonPath('data.choice_max_score', 2)
            ->assertJsonPath('data.percentage', 50)
            ->assertJsonPath('data.review.0.is_correct', true)
            ->assertJsonPath('data.review.1.is_correct', false);

        $progressResponse = $this->actingAs($user)->getJson("/api/v1/module-pages/{$page->id}");
        $progressResponse->assertOk()->assertJsonPath('data.progress.status', 'completed');
    }

    /**
     * BR-08: attempt yang sudah completed_at != null bersifat immutable.
     */
    public function test_resubmitting_completed_attempt_returns_409(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$quiz, , $questions] = $this->createJourneyQuiz($journey, 1);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $payload = ['choice_answers' => [
            ['quiz_question_id' => $questions[0]->id, 'quiz_choice_option_id' => $this->correctOptionIdFor($questions[0])],
        ], 'likert_answers' => []];

        $this->actingAs($user)->postJson("/api/v1/quiz-attempts/{$attemptId}/submit", $payload)->assertOk();

        $response = $this->actingAs($user)->postJson("/api/v1/quiz-attempts/{$attemptId}/submit", $payload);

        $response->assertStatus(409)->assertJsonPath('code', 'ATTEMPT_ALREADY_COMPLETED');
    }

    public function test_quiz_attempt_review_hidden_before_submission(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$quiz] = $this->createJourneyQuiz($journey, 1);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $response = $this->actingAs($user)->getJson("/api/v1/quiz-attempts/{$attemptId}");

        $response->assertOk();
        $this->assertArrayNotHasKey('review', $response->json('data'));
    }

    public function test_other_user_cannot_view_or_submit_someone_elses_attempt(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$quiz] = $this->createJourneyQuiz($journey, 1);

        $attemptId = $this->actingAs($owner)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")
            ->assertCreated()->json('data.attempt_id');

        $this->actingAs($intruder)->getJson("/api/v1/quiz-attempts/{$attemptId}")->assertForbidden();
        $this->actingAs($intruder)->postJson("/api/v1/quiz-attempts/{$attemptId}/submit", ['choice_answers' => [], 'likert_answers' => []])
            ->assertForbidden();
    }

    /**
     * BR-05: pretest sektor hanya dapat dikerjakan satu kali.
     */
    public function test_pretest_cannot_be_started_twice(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        [$quiz] = $this->createSectorQuiz($sector, QuizKind::Pretest);

        $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")->assertCreated();

        $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}/pretest")
            ->assertStatus(403)->assertJsonPath('code', 'QUIZ_NOT_ELIGIBLE');

        $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")
            ->assertStatus(403)->assertJsonPath('code', 'QUIZ_NOT_ELIGIBLE');
    }

    /**
     * BR-05: posttest terkunci sebelum seluruh journey di sektor selesai.
     */
    public function test_posttest_locked_until_all_journeys_completed(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        $this->createSectorQuiz($sector, QuizKind::Posttest);

        $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}/posttest")
            ->assertStatus(403)->assertJsonPath('code', 'QUIZ_NOT_ELIGIBLE');

        JourneyProgress::factory()->create([
            'user_id' => $user->id,
            'journey_id' => $journey->id,
            'status' => ProgressStatus::Completed,
        ]);

        $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}/posttest")->assertOk();
    }

    /**
     * BR-06: kuis journey boleh diulang; skor tertinggi yang dipakai untuk progres.
     */
    public function test_best_attempt_uses_highest_score_across_repeated_attempts(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$quiz, , $questions] = $this->createJourneyQuiz($journey, 2);

        $attemptService = app(QuizAttemptService::class);
        $scoringService = app(QuizScoringService::class);

        // Attempt 1: 1/2 benar (50%).
        $attempt1 = $attemptService->startAttempt($user, $quiz);
        $attempt1->load(['user', 'quizContent.modulePage.module.journey']);
        $scoringService->submit($attempt1, new QuizSubmissionData(
            choiceAnswers: [
                ['quiz_question_id' => $questions[0]->id, 'quiz_choice_option_id' => $this->correctOptionIdFor($questions[0])],
                ['quiz_question_id' => $questions[1]->id, 'quiz_choice_option_id' => $this->wrongOptionIdFor($questions[1])],
            ],
            likertAnswers: [],
        ));

        // Attempt 2: 2/2 benar (100%).
        $attempt2 = $attemptService->startAttempt($user, $quiz);
        $attempt2->load(['user', 'quizContent.modulePage.module.journey']);
        $scoringService->submit($attempt2, new QuizSubmissionData(
            choiceAnswers: [
                ['quiz_question_id' => $questions[0]->id, 'quiz_choice_option_id' => $this->correctOptionIdFor($questions[0])],
                ['quiz_question_id' => $questions[1]->id, 'quiz_choice_option_id' => $this->correctOptionIdFor($questions[1])],
            ],
            likertAnswers: [],
        ));

        $best = $attemptService->bestAttempt($user, $quiz);

        $this->assertSame($attempt2->id, $best?->id);
        $this->assertSame(2, $best->attempt_number);
    }

    /**
     * @return array{0: int, 1: array<int, mixed>}
     */
    private function submitFreshQuizAndCountQueries(int $questionCount): array
    {
        // Sektor/journey/user terisolasi per pengukuran, supaya state sebelum submit
        // (journey_progress/sector_progress belum ada baris sama sekali) identik untuk
        // tiap pengukuran — satu-satunya variabel yang beda cuma jumlah soal.
        $user = User::factory()->create();
        $sector = Sector::factory()->create();
        $journey = Journey::factory()->create(['sector_id' => $sector->id, 'order' => 1]);
        [$quiz, , $questions] = $this->createJourneyQuiz($journey, $questionCount);
        $attemptId = $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")->json('data.attempt_id');

        $answers = collect($questions)->map(fn ($q) => [
            'quiz_question_id' => $q->id,
            'quiz_choice_option_id' => $this->correctOptionIdFor($q),
        ])->all();

        DB::enableQueryLog();
        $response = $this->actingAs($user)->postJson("/api/v1/quiz-attempts/{$attemptId}/submit", [
            'choice_answers' => $answers,
            'likert_answers' => [],
        ]);
        $response->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        return [$queryCount, $answers];
    }

    public function test_submit_query_count_does_not_scale_with_question_count(): void
    {
        [$smallQueryCount] = $this->submitFreshQuizAndCountQueries(2);
        [$bigQueryCount] = $this->submitFreshQuizAndCountQueries(10);

        $this->assertSame($smallQueryCount, $bigQueryCount);
    }
}
