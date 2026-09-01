<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\JourneyLockedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ModulePageResource;
use App\Models\ModuleProgress;
use App\Services\Content\ContentTreeService;
use App\Services\Learning\JourneyAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ModulePageController extends Controller
{
    public function __construct(
        private readonly ContentTreeService $contentTree,
        private readonly JourneyAccessService $journeyAccess,
    ) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $page = $this->contentTree->loadPage($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $page->module->journey)) {
            throw new JourneyLockedException;
        }

        $page->setAttribute(
            'user_progress',
            ModuleProgress::query()->where('user_id', $request->user()->id)->where('module_page_id', $page->id)->first()
        );

        return ApiResponse::success(new ModulePageResource($page));
    }
}
