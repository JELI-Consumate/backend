<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ReflectionEntriesData
{
    /**
     * @param  array<int, array{reflection_question_id: int, answer_text: ?string}>  $entries  jawaban open_question
     * @param  array<int, array{reflection_checklist_item_id: int, is_checked: bool}>  $checklistAnswers  jawaban checklist (tidak ada benar/salah)
     */
    public function __construct(
        public array $entries,
        public array $checklistAnswers = [],
    ) {}
}
