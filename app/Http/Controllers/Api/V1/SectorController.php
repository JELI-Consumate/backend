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
use OpenApi\Attributes as OA;

final class SectorController extends Controller
{
    public function __construct(private readonly JourneyAccessService $journeyAccess) {}

    #[OA\Get(
        path: '/sectors',
        summary: 'Daftar sektor perlindungan konsumen aktif',
        tags: ['Katalog Pembelajaran'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK — diurutkan sesuai `order`, dilengkapi progres user per sektor',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
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

    #[OA\Get(
        path: '/sectors/{slug}',
        summary: 'Detail sektor + daftar journey (dengan status unlock/progres)',
        tags: ['Katalog Pembelajaran'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'e-commerce'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Sektor tidak ditemukan / tidak aktif'),
        ]
    )]
    public function show(Request $request, string $slug): JsonResponse
    {
        $sector = Sector::query()->active()->where('slug', $slug)->firstOrFail();

        $journeys = $sector->journeys()->orderBy('order')->get();

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
