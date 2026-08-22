<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\JourneyLockedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ModuleResource;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Services\Content\ContentTreeService;
use App\Services\Learning\JourneyAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class ModuleController extends Controller
{
    public function __construct(
        private readonly ContentTreeService $contentTree,
        private readonly JourneyAccessService $journeyAccess,
    ) {}

    #[OA\Get(
        path: '/modules/{id}',
        summary: 'Detail module + seluruh module_pages (konten polimorfik ter-resolve)',
        description: 'Cache 6 jam, ≤8 query per Definition of Done Fase 3. Progress user digabung setelah tree diambil dari cache.',
        tags: ['Katalog Pembelajaran'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
            new OA\Response(response: 404, description: 'Module tidak ditemukan'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $module = $this->contentTree->loadModuleTree($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $module->journey)) {
            throw new JourneyLockedException;
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
