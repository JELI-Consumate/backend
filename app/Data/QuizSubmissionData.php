<?php

declare(strict_types=1);

namespace App\Data;

final readonly class QuizSubmissionData
{
    /**
     * @param  array<int, array{quiz_question_id: int, quiz_choice_option_id: int}>  $choiceAnswers
     * @param  array<int, array{quiz_question_id: int, likert_scale_option_id: int}>  $likertAnswers
     */
    public function __construct(
        public array $choiceAnswers,
        public array $likertAnswers,
    ) {}
}
