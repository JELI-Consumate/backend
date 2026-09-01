<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ReflectionEntriesData
{
    /**
     * @param  array<int, array{reflection_question_id: string, answer_text: ?string}>  $entries  jawaban open_question
     * @param  array<int, array{reflection_checklist_item_id: string, is_checked: bool}>  $checklistAnswers  jawaban checklist (tidak ada benar/salah)
     */
    public function __construct(
        public array $entries,
        public array $checklistAnswers = [],
    ) {}
}
