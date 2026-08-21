<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\JourneyLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Progress\UpdatePositionRequest;
use App\Http\Resources\V1\JourneyProgressResource;
use App\Http\Resources\V1\ModuleProgressResource;
use App\Http\Resources\V1\SectorProgressResource;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\ModulePage;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Services\Learning\JourneyAccessService;
use App\Services\Progress\ProgressService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProgressController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly JourneyAccessService $journeyAccess,
    ) {}

    public function complete(Request $request, int $id): JsonResponse
    {
        $page = ModulePage::query()->with('module.journey')->findOrFail($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $page->module->journey)) {
            throw new JourneyLockedException;
        }

        $progress = $this->progress->markPageCompleted($request->user(), $page);

        return ApiResponse::success(new ModuleProgressResource($progress));
    }

    public function position(UpdatePositionRequest $request, int $id): JsonResponse
    {
        $page = ModulePage::query()->with('module.journey')->findOrFail($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $page->module->journey)) {
            throw new JourneyLockedException;
        }

        $progress = $this->progress->updateLastPosition($request->user(), $page, $request->integer('position'));

        return ApiResponse::success(new ModuleProgressResource($progress));
    }

    public function sectorProgress(Request $request, string $slug): JsonResponse
    {
        $sector = Sector::query()->active()->where('slug', $slug)->firstOrFail();

        $sector->setAttribute(
            'user_progress',
            SectorProgress::query()->where('user_id', $request->user()->id)->where('sector_id', $sector->id)->first()
        );

        return ApiResponse::success(new SectorProgressResource($sector));
    }

    public function journeyProgress(Request $request, int $id): JsonResponse
    {
        $journey = Journey::query()->findOrFail($id);

        $journey->setAttribute(
            'user_progress',
            JourneyProgress::query()->where('user_id', $request->user()->id)->where('journey_id', $journey->id)->first()
        );

        return ApiResponse::success(new JourneyProgressResource($journey));
    }

    /**
     * Ringkasan progres lintas sektor untuk dashboard — membaca kolom
     * terdenormalisasi sector_progress, tanpa agregasi realtime (06 §8).
     */
    public function summary(Request $request): AnonymousResourceCollection
    {
        $sectors = Sector::query()->active()->orderBy('order')->get();

        $progressBySectorId = SectorProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('sector_id', $sectors->pluck('id'))
            ->get()
            ->keyBy('sector_id');

        $sectors->each(
            fn (Sector $sector) => $sector->setAttribute('user_progress', $progressBySectorId->get($sector->id))
        );

        return SectorProgressResource::collection($sectors);
    }
}
