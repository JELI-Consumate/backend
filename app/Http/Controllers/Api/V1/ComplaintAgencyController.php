<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ComplaintAgencyDetailResource;
use App\Http\Resources\V1\ComplaintAgencyResource;
use App\Models\ComplaintAgency;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ComplaintAgencyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ComplaintAgencyResource::collection(
            ComplaintAgency::query()->active()->orderBy('order')->get()
        );
    }

    public function show(string $slug): JsonResponse
    {
        $agency = ComplaintAgency::query()
            ->active()
            ->with('channels')
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponse::success(new ComplaintAgencyDetailResource($agency));
    }
}
