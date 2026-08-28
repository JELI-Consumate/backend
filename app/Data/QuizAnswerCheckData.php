<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\QuizSegmentType;

final readonly class QuizAnswerCheckData
{
    public function __construct(
        public QuizSegmentType $type,
        public int $quizQuestionId,
        public ?int $quizChoiceOptionId = null,
        public ?int $likertScaleOptionId = null,
    ) {}
}
