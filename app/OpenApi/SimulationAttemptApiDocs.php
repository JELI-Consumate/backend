<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\SimulationAttemptController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class SimulationAttemptApiDocs
{
    #[OA\Get(
        path: '/simulations/{id}',
        summary: 'Skenario + item simulasi (correct_position disembunyikan)',
        tags: ['Simulasi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Simulasi tidak ditemukan'),
        ]
    )]
    public function show(): void {}

    #[OA\Post(
        path: '/simulations/{id}/attempts',
        summary: 'Mulai attempt simulasi baru (BR-01: journey harus unlocked)',
        tags: ['Simulasi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'simulation_content id'),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Attempt dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [new OA\Property(property: 'attempt_id', type: 'string')], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Journey terkunci (`JOURNEY_LOCKED`)'),
        ]
    )]
    public function startAttempt(): void {}

    #[OA\Post(
        path: '/simulation-attempts/{id}/check',
        summary: 'Cek 1 item jawaban (Duolingo-style: salah ditolak, tidak disimpan)',
        description: 'BR-08: attempt immutable setelah completed. Jawaban salah dibalas `correct=false` tanpa mengubah attempt — client boleh retry item yang sama. Attempt otomatis completed begitu seluruh item simulasi ini pernah dijawab benar.',
        tags: ['Simulasi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'simulation_attempt id'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['matching', 'ordering']),
                    new OA\Property(property: 'simulation_matching_pair_id', type: 'string', description: 'wajib kalau type=matching'),
                    new OA\Property(property: 'submitted_right_pair_id', type: 'string', description: 'wajib kalau type=matching'),
                    new OA\Property(property: 'simulation_ordering_step_id', type: 'string', description: 'wajib kalau type=ordering'),
                    new OA\Property(property: 'submitted_position', type: 'integer', minimum: 1, description: 'wajib kalau type=ordering'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '`data.correct` + state attempt terkini', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Attempt bukan milik user ini'),
            new OA\Response(response: 409, description: 'Attempt sudah selesai (`ATTEMPT_ALREADY_COMPLETED`)'),
            new OA\Response(response: 422, description: 'Pasangan/langkah tidak valid untuk simulasi ini'),
        ]
    )]
    public function checkAnswer(): void {}
}
