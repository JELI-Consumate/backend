<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\QuizAttempt;

final readonly class QuizAnswerCheckResult
{
    /**
     * [correct]/[correctOptionId]/[explanation] semuanya `null` untuk
     * pertanyaan segmen `likert` — tidak ada benar/salah di situ (lihat
     * `QuizScoringService::checkAnswer`).
     */
    public function __construct(
        public ?bool $correct,
        public ?string $correctOptionId,
        public ?string $explanation,
        public QuizAttempt $attempt,
    ) {}
}
