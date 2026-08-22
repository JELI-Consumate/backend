<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\SectorController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class SectorApiDocs
{
    #[OA\Get(
        path: '/sectors',
        summary: 'Daftar sektor perlindungan konsumen aktif',
        tags: ['Katalog Pembelajaran'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK — diurutkan sesuai `order`, dilengkapi progres user per sektor',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/sectors/{slug}',
        summary: 'Detail sektor + daftar journey (dengan status unlock/progres)',
        tags: ['Katalog Pembelajaran'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'e-commerce'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Sektor tidak ditemukan / tidak aktif'),
        ]
    )]
    public function show(): void {}
}
