<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\ProgressController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class ProgressApiDocs
{
    #[OA\Post(
        path: '/module-pages/{id}/complete',
        summary: 'Tandai halaman selesai (BR-11: idempotent)',
        tags: ['Progres'],
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
    public function complete(): void {}

    #[OA\Patch(
        path: '/module-pages/{id}/position',
        summary: 'Simpan posisi terakhir (detik video / indeks slide)',
        description: 'Menaikkan status not_started→in_progress, tidak pernah menurunkan dari completed.',
        tags: ['Progres'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['position'],
                properties: [new OA\Property(property: 'position', type: 'integer', minimum: 0, example: 42)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
            new OA\Response(response: 422, description: 'position invalid (negatif)'),
        ]
    )]
    public function position(): void {}

    #[OA\Get(
        path: '/progress/sectors/{slug}',
        summary: 'Progres user pada satu sektor',
        tags: ['Progres'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Sektor tidak ditemukan'),
        ]
    )]
    public function sectorProgress(): void {}

    #[OA\Get(
        path: '/progress/journeys/{id}',
        summary: 'Progres user pada satu journey',
        tags: ['Progres'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Journey tidak ditemukan'),
        ]
    )]
    public function journeyProgress(): void {}

    #[OA\Get(
        path: '/progress/summary',
        summary: 'Ringkasan progres lintas sektor untuk dashboard',
        description: 'Membaca kolom terdenormalisasi sector_progress, tanpa agregasi realtime (06 §8).',
        tags: ['Progres'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function summary(): void {}
}
