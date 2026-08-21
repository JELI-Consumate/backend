<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\QuizAttempt;

final readonly class QuizAttemptCompleted
{
    public function __construct(public QuizAttempt $attempt) {}
}
