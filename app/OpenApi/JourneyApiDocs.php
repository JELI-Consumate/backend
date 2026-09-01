<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\JourneyController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class JourneyApiDocs
{
    #[OA\Get(
        path: '/journeys/{id}',
        summary: 'Detail journey + daftar module (BR-01: 403 kalau terkunci)',
        tags: ['Katalog Pembelajaran'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
            new OA\Response(response: 404, description: 'Journey tidak ditemukan'),
        ]
    )]
    public function show(): void {}
}
