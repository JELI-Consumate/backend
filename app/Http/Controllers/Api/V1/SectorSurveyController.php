<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\SurveyKind;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SectorProgressResource;
use App\Models\Sector;
use App\Services\Learning\SectorSurveyService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SectorSurveyController extends Controller
{
    public function __construct(private readonly SectorSurveyService $surveys) {}

    public function completePretest(Request $request, string $slug): JsonResponse
    {
        return $this->complete($request, $slug, SurveyKind::Pretest);
    }

    public function completePosttest(Request $request, string $slug): JsonResponse
    {
        return $this->complete($request, $slug, SurveyKind::Posttest);
    }

    private function complete(Request $request, string $slug, SurveyKind $kind): JsonResponse
    {
        $sector = Sector::query()->active()->where('slug', $slug)->firstOrFail();

        $progress = $this->surveys->markCompleted($request->user(), $sector, $kind);
        $sector->setAttribute('user_progress', $progress);

        return ApiResponse::success(new SectorProgressResource($sector));
    }
}
