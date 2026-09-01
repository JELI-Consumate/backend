<?php

declare(strict_types=1);

namespace App\Data;

final readonly class QuizSubmissionData
{
    /**
     * @param  array<int, array{quiz_question_id: string, quiz_choice_option_id: string}>  $choiceAnswers
     * @param  array<int, array{quiz_question_id: string, likert_scale_option_id: string}>  $likertAnswers
     */
    public function __construct(
        public array $choiceAnswers,
        public array $likertAnswers,
    ) {}
}
