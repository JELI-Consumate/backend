<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\ModuleController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class ModuleApiDocs
{
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
    public function show(): void {}
}
