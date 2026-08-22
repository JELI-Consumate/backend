<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulation\SubmitSimulationAttemptRequest;
use App\Http\Resources\V1\Content\SimulationContentResource;
use App\Http\Resources\V1\SimulationAttemptResource;
use App\Models\SimulationAttempt;
use App\Models\SimulationContent;
use App\Services\Simulation\SimulationScoringService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SimulationAttemptController extends Controller
{
    public function __construct(private readonly SimulationScoringService $scoring) {}

    #[OA\Get(
        path: '/simulations/{id}',
        summary: 'Skenario + item simulasi (correct_position disembunyikan)',
        tags: ['Simulasi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Simulasi tidak ditemukan'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $simulation = SimulationContent::query()->with(['matchingPairs', 'orderingSteps'])->findOrFail($id);

        return ApiResponse::success(new SimulationContentResource($simulation));
    }

    #[OA\Post(
        path: '/simulations/{id}/attempts',
        summary: 'Mulai attempt simulasi baru (BR-01: journey harus unlocked)',
        tags: ['Simulasi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'simulation_content id'),
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
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
        ]
    )]
    public function startAttempt(Request $request, int $id): JsonResponse
    {
        $simulation = SimulationContent::query()->findOrFail($id);

        $attempt = $this->scoring->startAttempt($request->user(), $simulation);

        return ApiResponse::success(['attempt_id' => $attempt->id], status: 201);
    }

    #[OA\Post(
        path: '/simulation-attempts/{id}/submit',
        summary: 'Submit jawaban simulasi (BR-08: attempt immutable setelah selesai)',
        tags: ['Simulasi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'simulation_attempt id'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(
                    property: 'matching_answers',
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'simulation_matching_pair_id', type: 'integer'),
                        new OA\Property(property: 'submitted_right_pair_id', type: 'integer'),
                    ], type: 'object')
                ),
                new OA\Property(
                    property: 'ordering_answers',
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'simulation_ordering_step_id', type: 'integer'),
                        new OA\Property(property: 'submitted_position', type: 'integer', minimum: 1),
                    ], type: 'object')
                ),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Skor + koreksi per item', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Attempt bukan milik user ini'),
            new OA\Response(response: 409, description: 'Attempt sudah selesai (`ATTEMPT_ALREADY_COMPLETED`)'),
            new OA\Response(response: 422, description: 'Pasangan/langkah tidak valid untuk simulasi ini'),
        ]
    )]
    public function submit(SubmitSimulationAttemptRequest $request, int $id): JsonResponse
    {
        $attempt = SimulationAttempt::query()->with(['user', 'simulationContent.modulePage.module.journey'])->findOrFail($id);

        if ($attempt->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('Attempt ini bukan milik kamu.');
        }

        $attempt = $this->scoring->submit($attempt, $request->toData());
        $attempt->load(['matchingAnswers', 'orderingAnswers.orderingStep']);

        return ApiResponse::success(new SimulationAttemptResource($attempt));
    }
}
