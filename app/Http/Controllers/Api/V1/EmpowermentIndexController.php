<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\Gamification\EmpowermentIndexService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class EmpowermentIndexController extends Controller
{
    public function __construct(private readonly EmpowermentIndexService $empowermentIndex) {}

    #[OA\Get(
        path: '/empowerment-index',
        summary: 'Indeks Keberdayaan per sektor + agregat (BR-12)',
        description: '50% skor pengetahuan (choice) + 50% skor sikap (likert dinormalisasi), dari attempt posttest terakhir (fallback pretest). 0 kalau belum ada attempt.',
        tags: ['Gamifikasi'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'sectors', type: 'array', items: new OA\Items(properties: [
                            new OA\Property(property: 'sector_id', type: 'integer'),
                            new OA\Property(property: 'sector_slug', type: 'string'),
                            new OA\Property(property: 'sector_name', type: 'string'),
                            new OA\Property(property: 'empowerment_index', type: 'integer', minimum: 0, maximum: 100),
                        ], type: 'object')),
                        new OA\Property(property: 'aggregate', type: 'integer', minimum: 0, maximum: 100),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $sectors = Sector::query()->active()->orderBy('order')->get();

        $sectorIndexes = $sectors->map(fn (Sector $sector): array => [
            'sector_id' => $sector->id,
            'sector_slug' => $sector->slug,
            'sector_name' => $sector->name,
            'empowerment_index' => $this->empowermentIndex->calculate($request->user(), $sector),
        ]);

        $aggregate = $sectorIndexes->isNotEmpty()
            ? (int) round($sectorIndexes->avg('empowerment_index'))
            : 0;

        return ApiResponse::success([
            'sectors' => $sectorIndexes->values(),
            'aggregate' => $aggregate,
        ]);
    }
}
