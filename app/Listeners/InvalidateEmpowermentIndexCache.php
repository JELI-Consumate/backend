<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\QuizKind;
use App\Events\QuizAttemptCompleted;
use App\Support\CacheKey;
use Illuminate\Support\Facades\Cache;

/**
 * BR-12: EmpowermentIndexService cache 15 menit diforget saat attempt
 * pretest/posttest baru completed, supaya indeks tidak stale. Attempt kind
 * "quiz" (bukan pretest/posttest) tidak memengaruhi indeks, jadi diabaikan.
 */
final readonly class InvalidateEmpowermentIndexCache
{
    public function handle(QuizAttemptCompleted $event): void
    {
        $quizContent = $event->attempt->quizContent()->first();

        if ($quizContent === null || ! in_array($quizContent->kind, [QuizKind::Pretest, QuizKind::Posttest], true)) {
            return;
        }

        Cache::forget(CacheKey::empowermentIndex($event->attempt->user_id, $quizContent->sector_id));
    }
}
