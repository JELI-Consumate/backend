<?php

declare(strict_types=1);

namespace App\Services\Reflection;

use App\Data\ReflectionEntriesData;
use App\Enums\ReflectionQuestionType;
use App\Models\ReflectionContent;
use App\Models\ReflectionEntry;
use App\Models\User;
use App\Services\Progress\ProgressService;
use Illuminate\Support\Facades\Date;

final readonly class ReflectionService
{
    public function __construct(private ProgressService $progress) {}

    /**
     * Upsert seluruh jawaban refleksi user (satu panggilan bulk upsert, bukan loop
     * insert/update per baris) — idempotent lewat UNIQUE(user_id, reflection_question_id).
     * Kalau setelahnya seluruh open_question sudah terisi (BR-10), tandai halaman selesai.
     */
    public function upsertEntries(User $user, ReflectionContent $content, ReflectionEntriesData $data): void
    {
        if ($data->entries === []) {
            return;
        }

        $now = Date::now();

        $rows = array_map(fn (array $entry): array => [
            'user_id' => $user->id,
            'reflection_question_id' => $entry['reflection_question_id'],
            'answer_text' => $entry['answer_text'] ?? null,
            'is_checked' => $entry['is_checked'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $data->entries);

        ReflectionEntry::query()->upsert(
            $rows,
            ['user_id', 'reflection_question_id'],
            ['answer_text', 'is_checked', 'updated_at'],
        );

        if ($this->isCompleted($user, $content)) {
            $content->loadMissing('modulePage');

            if ($content->modulePage !== null) {
                $this->progress->markPageCompleted($user, $content->modulePage);
            }
        }
    }

    /**
     * BR-10: module refleksi selesai kalau seluruh pertanyaan open_question pada
     * module tersebut sudah terisi jawabannya. Checklist tidak menghalangi completion.
     */
    public function isCompleted(User $user, ReflectionContent $content): bool
    {
        $content->loadMissing('sections.questions');

        $openQuestionIds = $content->sections
            ->flatMap(fn ($section) => $section->questions)
            ->filter(fn ($question) => $question->question_type === ReflectionQuestionType::OpenQuestion)
            ->pluck('id');

        if ($openQuestionIds->isEmpty()) {
            return true;
        }

        $answeredCount = ReflectionEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('reflection_question_id', $openQuestionIds)
            ->whereNotNull('answer_text')
            ->where('answer_text', '!=', '')
            ->count();

        return $answeredCount >= $openQuestionIds->count();
    }
}
