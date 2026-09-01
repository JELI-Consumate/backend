<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\ReflectionEntryController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class ReflectionEntryApiDocs
{
    #[OA\Get(
        path: '/reflections/{id}',
        summary: 'Struktur pertanyaan refleksi + jawaban user sebelumnya (jurnal)',
        tags: ['Refleksi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Refleksi tidak ditemukan'),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/reflections/{id}/entries',
        summary: 'Upsert seluruh jawaban refleksi (BR-10)',
        description: 'Idempotent by unique index. Module refleksi selesai begitu semua open_question terisi — checklist tidak menghalangi completion (tidak ada benar/salah, murni penanda personal).',
        tags: ['Refleksi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(
                    property: 'entries',
                    type: 'array',
                    description: 'Jawaban question_type=open_question',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'reflection_question_id', type: 'string'),
                        new OA\Property(property: 'answer_text', type: 'string', nullable: true),
                    ], type: 'object')
                ),
                new OA\Property(
                    property: 'checklist_answers',
                    type: 'array',
                    description: 'Status centang item question_type=checklist (multi-item, tidak ada benar/salah)',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'reflection_checklist_item_id', type: 'string'),
                        new OA\Property(property: 'is_checked', type: 'boolean'),
                    ], type: 'object')
                ),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Refleksi tidak ditemukan'),
            new OA\Response(response: 422, description: 'Pertanyaan tidak valid untuk refleksi ini'),
        ]
    )]
    public function updateEntries(): void {}
}
