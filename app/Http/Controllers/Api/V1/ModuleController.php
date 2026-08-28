<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\JourneyLockedException;
use App\Exceptions\ModuleLockedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ModuleResource;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Services\Content\ContentTreeService;
use App\Services\Learning\JourneyAccessService;
use App\Services\Learning\ModuleAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ModuleController extends Controller
{
    public function __construct(
        private readonly ContentTreeService $contentTree,
        private readonly JourneyAccessService $journeyAccess,
        private readonly ModuleAccessService $moduleAccess,
    ) {}

    public function show(Request $request, int $id): JsonResponse
    {
        $module = $this->contentTree->loadModuleTree($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $module->journey)) {
            throw new JourneyLockedException;
        }

        if (! $this->moduleAccess->isUnlocked($request->user(), $module)) {
            throw new ModuleLockedException;
        }

        $progressByPageId = ModuleProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('module_page_id', $module->pages->pluck('id'))
            ->get()
            ->keyBy('module_page_id');

        $module->pages->each(
            fn (ModulePage $page) => $page->setAttribute('user_progress', $progressByPageId->get($page->id))
        );

        return ApiResponse::success(new ModuleResource($module));
    }
}
