<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\BadgeController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class BadgeApiDocs
{
    #[OA\Get(
        path: '/badges',
        summary: 'Seluruh badge + status earned/locked (BR-07)',
        tags: ['Gamifikasi'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'journey_id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'congratulation_message', type: 'string', nullable: true),
                        new OA\Property(property: 'motivational_message', type: 'string', nullable: true),
                        new OA\Property(property: 'icon_url', type: 'string', nullable: true),
                        new OA\Property(property: 'earned', type: 'boolean'),
                        new OA\Property(property: 'earned_at', type: 'string', format: 'date-time', nullable: true),
                    ], type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function index(): void {}
}
