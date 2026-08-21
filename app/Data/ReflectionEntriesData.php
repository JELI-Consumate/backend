<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ReflectionEntriesData
{
    /**
     * @param  array<int, array{reflection_question_id: int, answer_text: ?string, is_checked: ?bool}>  $entries
     */
    public function __construct(
        public array $entries,
    ) {}
}
