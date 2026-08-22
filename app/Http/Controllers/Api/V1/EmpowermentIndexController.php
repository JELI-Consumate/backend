<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\Gamification\EmpowermentIndexService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmpowermentIndexController extends Controller
{
    public function __construct(private readonly EmpowermentIndexService $empowermentIndex) {}

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
