<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\JourneyLockedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\JourneyDetailResource;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Services\Learning\JourneyAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class JourneyController extends Controller
{
    public function __construct(private readonly JourneyAccessService $journeyAccess) {}

    #[OA\Get(
        path: '/journeys/{id}',
        summary: 'Detail journey + daftar module (BR-01: 403 kalau terkunci)',
        tags: ['Katalog Pembelajaran'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
            new OA\Response(response: 404, description: 'Journey tidak ditemukan'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $journey = Journey::query()->with('modules')->findOrFail($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $journey)) {
            throw new JourneyLockedException;
        }

        $journey->setAttribute('is_unlocked', true);
        $journey->setAttribute(
            'user_progress',
            JourneyProgress::query()->where('user_id', $request->user()->id)->where('journey_id', $journey->id)->first()
        );

        return ApiResponse::success(new JourneyDetailResource($journey));
    }
}
