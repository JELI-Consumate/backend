<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\EmpowermentIndexController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class EmpowermentIndexApiDocs
{
    #[OA\Get(
        path: '/empowerment-index',
        summary: 'Indeks Keberdayaan per sektor + agregat (BR-12)',
        description: '50% skor pengetahuan (choice) + 50% skor sikap (likert dinormalisasi), dari attempt posttest terakhir (fallback pretest). 0 kalau belum ada attempt.',
        tags: ['Gamifikasi'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'sectors', type: 'array', items: new OA\Items(properties: [
                            new OA\Property(property: 'sector_id', type: 'string'),
                            new OA\Property(property: 'sector_slug', type: 'string'),
                            new OA\Property(property: 'sector_name', type: 'string'),
                            new OA\Property(property: 'empowerment_index', type: 'integer', minimum: 0, maximum: 100),
                        ], type: 'object')),
                        new OA\Property(property: 'aggregate', type: 'integer', minimum: 0, maximum: 100),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function index(): void {}
}
