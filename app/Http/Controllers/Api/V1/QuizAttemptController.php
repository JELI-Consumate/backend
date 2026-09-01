<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuizKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\CheckQuizAnswerRequest;
use App\Http\Requests\Quiz\SubmitQuizAttemptRequest;
use App\Http\Resources\V1\Content\QuizContentResource;
use App\Http\Resources\V1\QuizAttemptResource;
use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\Sector;
use App\Services\Quiz\QuizAttemptService;
use App\Services\Quiz\QuizScoringService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService $attempts,
        private readonly QuizScoringService $scoring,
    ) {}

    public function showQuiz(string $id): JsonResponse
    {
        $quiz = QuizContent::query()
            ->with(['segments.questions.choiceOptions', 'segments.likertScaleOptions'])
            ->findOrFail($id);

        return ApiResponse::success(new QuizContentResource($quiz));
    }

    public function startAttempt(Request $request, string $id): JsonResponse
    {
        $quiz = QuizContent::query()->findOrFail($id);

        $attempt = $this->attempts->startAttempt($request->user(), $quiz);

        return ApiResponse::success(['attempt_id' => $attempt->id], status: 201);
    }

    public function submit(SubmitQuizAttemptRequest $request, string $id): JsonResponse
    {
        $attempt = QuizAttempt::query()->with(['user', 'quizContent.modulePage.module.journey'])->findOrFail($id);

        Gate::authorize('submit', $attempt);

        $attempt = $this->scoring->submit($attempt, $request->toData());
        $attempt->load(['choiceAnswers.quizQuestion.choiceOptions']);

        return ApiResponse::success(new QuizAttemptResource($attempt));
    }

    public function checkAnswer(CheckQuizAnswerRequest $request, string $id): JsonResponse
    {
        $attempt = QuizAttempt::query()->with(['user', 'quizContent.modulePage.module.journey'])->findOrFail($id);

        Gate::authorize('check', $attempt);

        $result = $this->scoring->checkAnswer($attempt, $request->toData());
        $result->attempt->load(['choiceAnswers.quizQuestion.choiceOptions']);

        return ApiResponse::success([
            'correct' => $result->correct,
            'correct_option_id' => $result->correctOptionId,
            'explanation' => $result->explanation,
            'attempt' => new QuizAttemptResource($result->attempt),
        ]);
    }

    public function showAttempt(string $id): JsonResponse
    {
        $attempt = QuizAttempt::query()->with(['choiceAnswers.quizQuestion.choiceOptions'])->findOrFail($id);

        Gate::authorize('view', $attempt);

        return ApiResponse::success(new QuizAttemptResource($attempt));
    }

    public function pretest(Request $request, string $slug): JsonResponse
    {
        return $this->showSectorQuiz($request, $slug, QuizKind::Pretest);
    }

    public function posttest(Request $request, string $slug): JsonResponse
    {
        return $this->showSectorQuiz($request, $slug, QuizKind::Posttest);
    }

    private function showSectorQuiz(Request $request, string $slug, QuizKind $kind): JsonResponse
    {
        $sector = Sector::query()->active()->where('slug', $slug)->firstOrFail();

        $quiz = QuizContent::query()
            ->with(['segments.questions.choiceOptions', 'segments.likertScaleOptions'])
            ->where('sector_id', $sector->id)
            ->where('kind', $kind)
            ->firstOrFail();

        $this->attempts->guardEligibility($request->user(), $quiz);

        return ApiResponse::success(new QuizContentResource($quiz));
    }
}
