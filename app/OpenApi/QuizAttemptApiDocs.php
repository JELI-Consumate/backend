<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk App\Http\Controllers\Api\V1\QuizAttemptController.
 * Dipisah dari controller supaya controller tetap ringkas.
 */
final class QuizAttemptApiDocs
{
    #[OA\Get(
        path: '/quizzes/{id}',
        summary: 'Soal kuis journey (mode "soal", tidak ada is_correct)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Kuis tidak ditemukan'),
        ]
    )]
    public function showQuiz(): void {}

    #[OA\Post(
        path: '/quizzes/{id}/attempts',
        summary: 'Mulai attempt kuis baru (BR-05, BR-06)',
        description: 'BR-01: journey harus unlocked. BR-05: pretest sekali, posttest butuh seluruh journey wajib selesai.',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'quiz_content id'),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Attempt dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [new OA\Property(property: 'attempt_id', type: 'integer')], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Belum eligible (`QUIZ_NOT_ELIGIBLE`) atau journey terkunci'),
        ]
    )]
    public function startAttempt(): void {}

    #[OA\Post(
        path: '/quiz-attempts/{id}/submit',
        summary: 'Submit jawaban kuis (BR-08: attempt immutable setelah selesai)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'quiz_attempt id'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(
                    property: 'choice_answers',
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'quiz_question_id', type: 'integer'),
                        new OA\Property(property: 'quiz_choice_option_id', type: 'integer'),
                    ], type: 'object')
                ),
                new OA\Property(
                    property: 'likert_answers',
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'quiz_question_id', type: 'integer'),
                        new OA\Property(property: 'likert_scale_option_id', type: 'integer'),
                    ], type: 'object')
                ),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Skor + koreksi per soal', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Attempt bukan milik user ini'),
            new OA\Response(response: 409, description: 'Attempt sudah selesai (`ATTEMPT_ALREADY_COMPLETED`)'),
            new OA\Response(response: 422, description: 'Jawaban tidak valid untuk soal ini'),
        ]
    )]
    public function submit(): void {}

    #[OA\Post(
        path: '/quiz-attempts/{id}/check',
        summary: 'Cek 1 pertanyaan per panggilan (gaya ujian, bukan Duolingo-style)',
        description: 'BR-08: attempt immutable setelah completed. Jawaban SALAH tetap disimpan permanen (soal langsung terkunci untuk attempt ini, tidak seperti simulasi yang boleh dicoba lagi). Attempt otomatis completed begitu seluruh pertanyaan pernah dicek.',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'quiz_attempt id'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'quiz_question_id'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['multiple_choice', 'likert']),
                    new OA\Property(property: 'quiz_question_id', type: 'integer'),
                    new OA\Property(property: 'quiz_choice_option_id', type: 'integer', description: 'wajib kalau type=multiple_choice'),
                    new OA\Property(property: 'likert_scale_option_id', type: 'integer', description: 'wajib kalau type=likert'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '`data.correct`/`correct_option_id`/`explanation` + state attempt terkini', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Attempt bukan milik user ini'),
            new OA\Response(response: 409, description: 'Attempt sudah selesai (`ATTEMPT_ALREADY_COMPLETED`)'),
            new OA\Response(response: 422, description: 'Pertanyaan/jawaban tidak valid untuk kuis ini'),
        ]
    )]
    public function checkAnswer(): void {}

    #[OA\Get(
        path: '/quiz-attempts/{id}',
        summary: 'Detail attempt (mode "pembahasan" kalau sudah selesai)',
        description: 'is_correct/explanation hanya muncul kalau completed_at != null. Hanya pemilik attempt yang bisa akses.',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Attempt bukan milik user ini'),
            new OA\Response(response: 404, description: 'Attempt tidak ditemukan'),
        ]
    )]
    public function showAttempt(): void {}

    #[OA\Get(
        path: '/sectors/{slug}/pretest',
        summary: 'Ambil pretest sektor (403 kalau belum eligible)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Belum eligible (`QUIZ_NOT_ELIGIBLE`) — mis. pretest sudah pernah dikerjakan'),
            new OA\Response(response: 404, description: 'Sektor/pretest tidak ditemukan'),
        ]
    )]
    public function pretest(): void {}

    #[OA\Get(
        path: '/sectors/{slug}/posttest',
        summary: 'Ambil posttest sektor (403 kalau belum eligible)',
        tags: ['Kuis'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 403, description: 'Belum eligible (`QUIZ_NOT_ELIGIBLE`) — mis. journey wajib belum semua selesai'),
            new OA\Response(response: 404, description: 'Sektor/posttest tidak ditemukan'),
        ]
    )]
    public function posttest(): void {}
}
