<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reflection\StoreReflectionEntriesRequest;
use App\Http\Resources\V1\ReflectionDetailResource;
use App\Models\ReflectionChecklistAnswer;
use App\Models\ReflectionContent;
use App\Models\ReflectionEntry;
use App\Models\ReflectionQuestion;
use App\Services\Reflection\ReflectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class ReflectionEntryController extends Controller
{
    public function __construct(private readonly ReflectionService $reflection) {}

    #[OA\Get(
        path: '/reflections/{id}',
        summary: 'Struktur pertanyaan refleksi + jawaban user sebelumnya (jurnal)',
        tags: ['Refleksi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Refleksi tidak ditemukan'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $content = ReflectionContent::query()->with('sections.questions.checklistItems')->findOrFail($id);

        $this->mergeUserEntries($request, $content);

        return ApiResponse::success(new ReflectionDetailResource($content));
    }

    #[OA\Put(
        path: '/reflections/{id}/entries',
        summary: 'Upsert seluruh jawaban refleksi (BR-10)',
        description: 'Idempotent by unique index. Module refleksi selesai begitu semua open_question terisi — checklist tidak menghalangi completion (tidak ada benar/salah, murni penanda personal).',
        tags: ['Refleksi'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(
                    property: 'entries',
                    type: 'array',
                    description: 'Jawaban question_type=open_question',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'reflection_question_id', type: 'integer'),
                        new OA\Property(property: 'answer_text', type: 'string', nullable: true),
                    ], type: 'object')
                ),
                new OA\Property(
                    property: 'checklist_answers',
                    type: 'array',
                    description: 'Status centang item question_type=checklist (multi-item, tidak ada benar/salah)',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'reflection_checklist_item_id', type: 'integer'),
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
    public function updateEntries(StoreReflectionEntriesRequest $request, int $id): JsonResponse
    {
        $content = ReflectionContent::query()->with('sections.questions.checklistItems')->findOrFail($id);

        $this->reflection->upsertEntries($request->user(), $content, $request->toData());

        $content->refresh()->load('sections.questions.checklistItems');
        $this->mergeUserEntries($request, $content);

        return ApiResponse::success(new ReflectionDetailResource($content));
    }

    private function mergeUserEntries(Request $request, ReflectionContent $content): void
    {
        $questions = $content->sections->flatMap(fn ($section) => $section->questions);
        $questionIds = $questions->pluck('id');
        $checklistItemIds = $questions->flatMap(fn ($question) => $question->checklistItems)->pluck('id');

        $entriesByQuestionId = ReflectionEntry::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('reflection_question_id', $questionIds)
            ->get()
            ->keyBy('reflection_question_id');

        $checklistAnswersByItemId = ReflectionChecklistAnswer::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('reflection_checklist_item_id', $checklistItemIds)
            ->get()
            ->keyBy('reflection_checklist_item_id');

        $content->sections->each(function ($section) use ($entriesByQuestionId, $checklistAnswersByItemId): void {
            $section->questions->each(function (ReflectionQuestion $question) use ($entriesByQuestionId, $checklistAnswersByItemId): void {
                $question->setAttribute('user_entry', $entriesByQuestionId->get($question->id));
                $question->checklistItems->each(
                    fn ($item) => $item->setAttribute('user_checked', $checklistAnswersByItemId->get($item->id)?->is_checked ?? false)
                );
            });
        });
    }
}
