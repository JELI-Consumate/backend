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
use OpenApi\Attributes as OA;

final class ProgressController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly JourneyAccessService $journeyAccess,
    ) {}

    #[OA\Post(
        path: '/module-pages/{id}/complete',
        summary: 'Tandai halaman selesai (BR-11: idempotent)',
        tags: ['Progres'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
            new OA\Response(response: 404, description: 'Halaman tidak ditemukan'),
        ]
    )]
    public function complete(Request $request, int $id): JsonResponse
    {
        $page = ModulePage::query()->with('module.journey')->findOrFail($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $page->module->journey)) {
            throw new JourneyLockedException;
        }

        $progress = $this->progress->markPageCompleted($request->user(), $page);

        return ApiResponse::success(new ModuleProgressResource($progress));
    }

    #[OA\Patch(
        path: '/module-pages/{id}/position',
        summary: 'Simpan posisi terakhir (detik video / indeks slide)',
        description: 'Menaikkan status not_started→in_progress, tidak pernah menurunkan dari completed.',
        tags: ['Progres'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['position'],
                properties: [new OA\Property(property: 'position', type: 'integer', minimum: 0, example: 42)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
            new OA\Response(response: 422, description: 'position invalid (negatif)'),
        ]
    )]
    public function position(UpdatePositionRequest $request, int $id): JsonResponse
    {
        $page = ModulePage::query()->with('module.journey')->findOrFail($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $page->module->journey)) {
            throw new JourneyLockedException;
        }

        $progress = $this->progress->updateLastPosition($request->user(), $page, $request->integer('position'));

        return ApiResponse::success(new ModuleProgressResource($progress));
    }

    #[OA\Get(
        path: '/progress/sectors/{slug}',
        summary: 'Progres user pada satu sektor',
        tags: ['Progres'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Sektor tidak ditemukan'),
        ]
    )]
    public function sectorProgress(Request $request, string $slug): JsonResponse
    {
        $sector = Sector::query()->active()->where('slug', $slug)->firstOrFail();

        $sector->setAttribute(
            'user_progress',
            SectorProgress::query()->where('user_id', $request->user()->id)->where('sector_id', $sector->id)->first()
        );

        return ApiResponse::success(new SectorProgressResource($sector));
    }

    #[OA\Get(
        path: '/progress/journeys/{id}',
        summary: 'Progres user pada satu journey',
        tags: ['Progres'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Journey tidak ditemukan'),
        ]
    )]
    public function journeyProgress(Request $request, int $id): JsonResponse
    {
        $journey = Journey::query()->findOrFail($id);

        $journey->setAttribute(
            'user_progress',
            JourneyProgress::query()->where('user_id', $request->user()->id)->where('journey_id', $journey->id)->first()
        );

        return ApiResponse::success(new JourneyProgressResource($journey));
    }

    #[OA\Get(
        path: '/progress/summary',
        summary: 'Ringkasan progres lintas sektor untuk dashboard',
        description: 'Membaca kolom terdenormalisasi sector_progress, tanpa agregasi realtime (06 §8).',
        tags: ['Progres'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
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
