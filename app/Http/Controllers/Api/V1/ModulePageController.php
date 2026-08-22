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
use OpenApi\Attributes as OA;

final class ModulePageController extends Controller
{
    public function __construct(
        private readonly ContentTreeService $contentTree,
        private readonly JourneyAccessService $journeyAccess,
    ) {}

    #[OA\Get(
        path: '/module-pages/{id}',
        summary: 'Lazy-load 1 halaman module (konten penuh, tidak di-cache)',
        tags: ['Katalog Pembelajaran'],
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
    public function show(Request $request, int $id): JsonResponse
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
