<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\QuizSubmissionData;
use App\Enums\ProgressStatus;
use App\Events\QuizAttemptCompleted;
use App\Exceptions\InvalidSubmissionException;
use App\Models\Journey;
use App\Models\LikertScaleOption;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizAttempt;
use App\Models\QuizChoiceOption;
use App\Models\QuizContent;
use App\Models\QuizQuestion;
use App\Models\QuizSegment;
use App\Models\User;
use App\Services\Quiz\QuizScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class QuizScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuizWithTwoChoiceQuestions(int $passingScore = 70): array
    {
        $journey = Journey::factory()->create();
        $quizContent = QuizContent::factory()->create(['journey_id' => $journey->id, 'passing_score' => $passingScore]);
        $segment = QuizSegment::factory()->create(['quiz_content_id' => $quizContent->id]);

        $q1 = QuizQuestion::factory()->create(['quiz_segment_id' => $segment->id]);
        $q1Correct = QuizChoiceOption::factory()->create(['quiz_question_id' => $q1->id, 'is_correct' => true]);
        $q1Wrong = QuizChoiceOption::factory()->create(['quiz_question_id' => $q1->id, 'is_correct' => false]);

        $q2 = QuizQuestion::factory()->create(['quiz_segment_id' => $segment->id]);
        $q2Correct = QuizChoiceOption::factory()->create(['quiz_question_id' => $q2->id, 'is_correct' => true]);
        $q2Wrong = QuizChoiceOption::factory()->create(['quiz_question_id' => $q2->id, 'is_correct' => false]);

        return compact('journey', 'quizContent', 'segment', 'q1', 'q1Correct', 'q1Wrong', 'q2', 'q2Correct', 'q2Wrong');
    }

    public function test_submit_scores_choice_answers_and_marks_passed_when_above_passing_score(): void
    {
        Event::fake();
        ['quizContent' => $quizContent, 'q1' => $q1, 'q1Correct' => $q1Correct, 'q2' => $q2, 'q2Correct' => $q2Correct] =
            $this->makeQuizWithTwoChoiceQuestions(passingScore: 70);

        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_content_id' => $quizContent->id]);

        $data = new QuizSubmissionData(
            choiceAnswers: [
                ['quiz_question_id' => $q1->id, 'quiz_choice_option_id' => $q1Correct->id],
                ['quiz_question_id' => $q2->id, 'quiz_choice_option_id' => $q2Correct->id],
            ],
            likertAnswers: [],
        );

        $result = app(QuizScoringService::class)->submit($attempt, $data);

        $this->assertSame(2, $result->choice_score);
        $this->assertSame(2, $result->choice_max_score);
        $this->assertTrue($result->passed);
        $this->assertNotNull($result->completed_at);
    }

    public function test_submit_marks_failed_when_percentage_below_passing_score(): void
    {
        Event::fake();
        ['quizContent' => $quizContent, 'q1' => $q1, 'q1Correct' => $q1Correct, 'q2' => $q2, 'q2Wrong' => $q2Wrong] =
            $this->makeQuizWithTwoChoiceQuestions(passingScore: 70);

        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_content_id' => $quizContent->id]);

        $data = new QuizSubmissionData(
            choiceAnswers: [
                ['quiz_question_id' => $q1->id, 'quiz_choice_option_id' => $q1Correct->id],
                ['quiz_question_id' => $q2->id, 'quiz_choice_option_id' => $q2Wrong->id],
            ],
            likertAnswers: [],
        );

        $result = app(QuizScoringService::class)->submit($attempt, $data);

        $this->assertSame(1, $result->choice_score);
        $this->assertFalse($result->passed);
    }

    public function test_submit_computes_likert_average(): void
    {
        Event::fake();
        ['quizContent' => $quizContent, 'segment' => $segment, 'q1' => $q1, 'q1Correct' => $q1Correct] =
            $this->makeQuizWithTwoChoiceQuestions();

        $likertQuestion = QuizQuestion::factory()->create(['quiz_segment_id' => $segment->id]);
        $optionValue2 = LikertScaleOption::factory()->create(['quiz_segment_id' => $segment->id, 'value' => 2]);
        LikertScaleOption::factory()->create(['quiz_segment_id' => $segment->id, 'value' => 4]);

        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_content_id' => $quizContent->id]);

        $data = new QuizSubmissionData(
            choiceAnswers: [['quiz_question_id' => $q1->id, 'quiz_choice_option_id' => $q1Correct->id]],
            likertAnswers: [['quiz_question_id' => $likertQuestion->id, 'likert_scale_option_id' => $optionValue2->id]],
        );

        $result = app(QuizScoringService::class)->submit($attempt, $data);

        // Rata-rata dihitung dari value likert_scale_options yang dipilih (bukan question), jadi 2.0.
        $this->assertEqualsWithDelta(2.0, (float) $result->likert_average, 0.001);
    }

    public function test_submit_throws_when_attempt_already_completed(): void
    {
        Event::fake();
        ['quizContent' => $quizContent] = $this->makeQuizWithTwoChoiceQuestions();

        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $quizContent->id,
        ]);

        $this->expectException(InvalidSubmissionException::class);

        app(QuizScoringService::class)->submit($attempt, new QuizSubmissionData(choiceAnswers: [], likertAnswers: []));
    }

    public function test_submit_marks_linked_module_page_completed(): void
    {
        Event::fake();
        ['journey' => $journey, 'quizContent' => $quizContent, 'q1' => $q1, 'q1Correct' => $q1Correct] =
            $this->makeQuizWithTwoChoiceQuestions();

        $module = Module::factory()->create(['journey_id' => $journey->id]);
        $page = ModulePage::factory()->create([
            'module_id' => $module->id,
            'contentable_type' => 'quiz',
            'contentable_id' => $quizContent->id,
        ]);

        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_content_id' => $quizContent->id]);

        $data = new QuizSubmissionData(
            choiceAnswers: [['quiz_question_id' => $q1->id, 'quiz_choice_option_id' => $q1Correct->id]],
            likertAnswers: [],
        );

        app(QuizScoringService::class)->submit($attempt, $data);

        $this->assertDatabaseHas('module_progress', [
            'user_id' => $user->id,
            'module_page_id' => $page->id,
            'status' => ProgressStatus::Completed->value,
        ]);
    }

    public function test_submit_dispatches_quiz_attempt_completed_event(): void
    {
        Event::fake();
        ['quizContent' => $quizContent, 'q1' => $q1, 'q1Correct' => $q1Correct] = $this->makeQuizWithTwoChoiceQuestions();

        $user = User::factory()->create();
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_content_id' => $quizContent->id]);

        $data = new QuizSubmissionData(
            choiceAnswers: [['quiz_question_id' => $q1->id, 'quiz_choice_option_id' => $q1Correct->id]],
            likertAnswers: [],
        );

        app(QuizScoringService::class)->submit($attempt, $data);

        Event::assertDispatched(QuizAttemptCompleted::class, fn (QuizAttemptCompleted $event): bool => $event->attempt->id === $attempt->id);
    }
}
