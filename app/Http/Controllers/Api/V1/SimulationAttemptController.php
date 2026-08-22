<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulation\CheckSimulationAnswerRequest;
use App\Http\Resources\V1\Content\SimulationContentResource;
use App\Http\Resources\V1\SimulationAttemptResource;
use App\Models\SimulationAttempt;
use App\Models\SimulationContent;
use App\Services\Simulation\SimulationScoringService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SimulationAttemptController extends Controller
{
    public function __construct(private readonly SimulationScoringService $scoring) {}

    public function show(int $id): JsonResponse
    {
        $simulation = SimulationContent::query()->with(['matchingPairs', 'orderingSteps'])->findOrFail($id);

        return ApiResponse::success(new SimulationContentResource($simulation));
    }

    public function startAttempt(Request $request, int $id): JsonResponse
    {
        $simulation = SimulationContent::query()->findOrFail($id);

        $attempt = $this->scoring->startAttempt($request->user(), $simulation);

        return ApiResponse::success(['attempt_id' => $attempt->id], status: 201);
    }

    public function checkAnswer(CheckSimulationAnswerRequest $request, int $id): JsonResponse
    {
        $attempt = SimulationAttempt::query()->with(['user', 'simulationContent.modulePage.module.journey'])->findOrFail($id);

        if ($attempt->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('Attempt ini bukan milik kamu.');
        }

        $result = $this->scoring->checkAnswer($attempt, $request->toData());
        $result->attempt->load(['matchingAnswers', 'orderingAnswers.orderingStep']);

        return ApiResponse::success([
            'correct' => $result->correct,
            'attempt' => new SimulationAttemptResource($result->attempt),
        ]);
    }
}
