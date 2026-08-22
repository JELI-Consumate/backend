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

final class JourneyController extends Controller
{
    public function __construct(private readonly JourneyAccessService $journeyAccess) {}

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
