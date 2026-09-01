<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\SectorSurveyController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class SectorSurveyApiDocs
{
    #[OA\Post(
        path: '/sectors/{slug}/pretest-survey/complete',
        summary: 'Tandai survei pretest (Google Form) sektor sudah diisi (self-report)',
        tags: ['Survei Sektor'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'e-commerce'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Sektor tidak ditemukan, atau link survei belum diisi admin (`SURVEY_NOT_CONFIGURED`)'),
        ]
    )]
    public function completePretest(): void {}

    #[OA\Post(
        path: '/sectors/{slug}/posttest-survey/complete',
        summary: 'Tandai survei posttest (Google Form) sektor sudah diisi (self-report)',
        tags: ['Survei Sektor'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'e-commerce'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Sektor tidak ditemukan, atau link survei belum diisi admin (`SURVEY_NOT_CONFIGURED`)'),
        ]
    )]
    public function completePosttest(): void {}
}
