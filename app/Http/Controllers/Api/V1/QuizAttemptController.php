<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuizKind;
use App\Http\Controllers\Controller;
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
use OpenApi\Attributes as OA;

final class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService $attempts,
        private readonly QuizScoringService $scoring,
    ) {}

    #[OA\Get(
        path: '/quizzes/{id}',
        summary: 'Soal kuis journey (mode "soal", tidak ada is_correct)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Kuis tidak ditemukan'),
        ]
    )]
    public function showQuiz(int $id): JsonResponse
    {
        $quiz = QuizContent::query()
            ->with(['segments.questions.choiceOptions', 'segments.likertScaleOptions'])
            ->findOrFail($id);

        return ApiResponse::success(new QuizContentResource($quiz));
    }

    #[OA\Post(
        path: '/quizzes/{id}/attempts',
        summary: 'Mulai attempt kuis baru (BR-05, BR-06)',
        description: 'BR-01: journey harus unlocked. BR-05: pretest sekali, posttest butuh seluruh journey wajib selesai.',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'quiz_content id'),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Attempt dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [new OA\Property(property: 'attempt_id', type: 'integer')], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Belum eligible (`QUIZ_NOT_ELIGIBLE`) atau journey terkunci'),
        ]
    )]
    public function startAttempt(Request $request, int $id): JsonResponse
    {
        $quiz = QuizContent::query()->findOrFail($id);

        $attempt = $this->attempts->startAttempt($request->user(), $quiz);

        return ApiResponse::success(['attempt_id' => $attempt->id], status: 201);
    }

    #[OA\Post(
        path: '/quiz-attempts/{id}/submit',
        summary: 'Submit jawaban kuis (BR-08: attempt immutable setelah selesai)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'quiz_attempt id'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(
                    property: 'choice_answers',
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'quiz_question_id', type: 'integer'),
                        new OA\Property(property: 'quiz_choice_option_id', type: 'integer'),
                    ], type: 'object')
                ),
                new OA\Property(
                    property: 'likert_answers',
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'quiz_question_id', type: 'integer'),
                        new OA\Property(property: 'likert_scale_option_id', type: 'integer'),
                    ], type: 'object')
                ),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Skor + koreksi per soal', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Attempt bukan milik user ini'),
            new OA\Response(response: 409, description: 'Attempt sudah selesai (`ATTEMPT_ALREADY_COMPLETED`)'),
            new OA\Response(response: 422, description: 'Jawaban tidak valid untuk soal ini'),
        ]
    )]
    public function submit(SubmitQuizAttemptRequest $request, int $id): JsonResponse
    {
        $attempt = QuizAttempt::query()->with(['user', 'quizContent.modulePage.module.journey'])->findOrFail($id);

        Gate::authorize('submit', $attempt);

        $attempt = $this->scoring->submit($attempt, $request->toData());
        $attempt->load(['choiceAnswers.quizQuestion.choiceOptions']);

        return ApiResponse::success(new QuizAttemptResource($attempt));
    }

    #[OA\Get(
        path: '/quiz-attempts/{id}',
        summary: 'Detail attempt (mode "pembahasan" kalau sudah selesai)',
        description: 'is_correct/explanation hanya muncul kalau completed_at != null. Hanya pemilik attempt yang bisa akses.',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Attempt bukan milik user ini'),
            new OA\Response(response: 404, description: 'Attempt tidak ditemukan'),
        ]
    )]
    public function showAttempt(int $id): JsonResponse
    {
        $attempt = QuizAttempt::query()->with(['choiceAnswers.quizQuestion.choiceOptions'])->findOrFail($id);

        Gate::authorize('view', $attempt);

        return ApiResponse::success(new QuizAttemptResource($attempt));
    }

    #[OA\Get(
        path: '/sectors/{slug}/pretest',
        summary: 'Ambil pretest sektor (403 kalau belum eligible)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Belum eligible (`QUIZ_NOT_ELIGIBLE`) — mis. pretest sudah pernah dikerjakan'),
            new OA\Response(response: 404, description: 'Sektor/pretest tidak ditemukan'),
        ]
    )]
    public function pretest(Request $request, string $slug): JsonResponse
    {
        return $this->showSectorQuiz($request, $slug, QuizKind::Pretest);
    }

    #[OA\Get(
        path: '/sectors/{slug}/posttest',
        summary: 'Ambil posttest sektor (403 kalau belum eligible)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Belum eligible (`QUIZ_NOT_ELIGIBLE`) — mis. journey wajib belum semua selesai'),
            new OA\Response(response: 404, description: 'Sektor/posttest tidak ditemukan'),
        ]
    )]
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
