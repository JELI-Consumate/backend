<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SectorResource;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Services\Learning\JourneyAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SectorController extends Controller
{
    public function __construct(private readonly JourneyAccessService $journeyAccess) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sectors = Sector::query()->active()->orderBy('order')->paginate(50);

        $progressBySectorId = SectorProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('sector_id', $sectors->pluck('id'))
            ->get()
            ->keyBy('sector_id');

        $sectors->getCollection()->each(
            fn (Sector $sector) => $sector->setAttribute('user_progress', $progressBySectorId->get($sector->id))
        );

        return SectorResource::collection($sectors);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $sector = Sector::query()->active()->where('slug', $slug)->firstOrFail();

        $journeys = $sector->journeys()->withCount('modules')->orderBy('order')->get();

        $unlockedMap = $this->journeyAccess->unlockedMapForSector($request->user(), $sector);

        $progressByJourneyId = JourneyProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('journey_id', $journeys->pluck('id'))
            ->get()
            ->keyBy('journey_id');

        $journeys->each(function (Journey $journey) use ($unlockedMap, $progressByJourneyId): void {
            $journey->setAttribute('is_unlocked', $unlockedMap[$journey->id] ?? false);
            $journey->setAttribute('user_progress', $progressByJourneyId->get($journey->id));
        });

        $sector->setAttribute('journey_list', $journeys);
        $sector->setAttribute(
            'user_progress',
            SectorProgress::query()->where('user_id', $request->user()->id)->where('sector_id', $sector->id)->first()
        );

        return ApiResponse::success(new SectorResource($sector));
    }
}
