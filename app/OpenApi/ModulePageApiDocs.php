<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\ModulePageController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class ModulePageApiDocs
{
    #[OA\Get(
        path: '/module-pages/{id}',
        summary: 'Lazy-load 1 halaman module (konten penuh, tidak di-cache)',
        tags: ['Katalog Pembelajaran'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
            new OA\Response(response: 404, description: 'Halaman tidak ditemukan'),
        ]
    )]
    public function show(): void {}
}
