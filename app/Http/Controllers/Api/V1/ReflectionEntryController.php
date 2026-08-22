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

final class ReflectionEntryController extends Controller
{
    public function __construct(private readonly ReflectionService $reflection) {}

    public function show(Request $request, int $id): JsonResponse
    {
        $content = ReflectionContent::query()->with('sections.questions.checklistItems')->findOrFail($id);

        $this->mergeUserEntries($request, $content);

        return ApiResponse::success(new ReflectionDetailResource($content));
    }

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
