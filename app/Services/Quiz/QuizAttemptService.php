<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Enums\ProgressStatus;
use App\Enums\QuizKind;
use App\Exceptions\JourneyLockedException;
use App\Exceptions\QuizNotEligibleException;
use App\Models\JourneyProgress;
use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\User;
use App\Services\Learning\JourneyAccessService;

final readonly class QuizAttemptService
{
    public function __construct(private JourneyAccessService $journeyAccess) {}

    /**
     * BR-05: pretest sektor hanya dapat dikerjakan satu kali; posttest hanya
     * terbuka setelah seluruh journey published di sektor selesai. Kuis journey
     * (kind=quiz) tidak dibatasi (BR-06), tapi tetap butuh journey-nya unlocked.
     */
    public function guardEligibility(User $user, QuizContent $quizContent): void
    {
        if ($quizContent->kind === QuizKind::Quiz) {
            $quizContent->loadMissing('journey');

            if (! $this->journeyAccess->isUnlocked($user, $quizContent->journey)) {
                throw new JourneyLockedException;
            }

            return;
        }

        if ($quizContent->kind === QuizKind::Pretest) {
            $alreadyAttempted = QuizAttempt::query()
                ->where('user_id', $user->id)
                ->where('quiz_content_id', $quizContent->id)
                ->exists();

            if ($alreadyAttempted) {
                throw new QuizNotEligibleException('Pretest sektor ini sudah pernah dikerjakan.');
            }

            return;
        }

        // kind === Posttest
        $journeyIds = $quizContent->sector->journeys()->pluck('id');

        $completedCount = $journeyIds->isEmpty() ? 0 : JourneyProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('journey_id', $journeyIds)
            ->where('status', ProgressStatus::Completed)
            ->count();

        if ($journeyIds->isEmpty() || $completedCount < $journeyIds->count()) {
            throw new QuizNotEligibleException('Selesaikan seluruh journey di sektor ini sebelum mengerjakan posttest.');
        }
    }

    /**
     * BR-06: attempt_number naik tiap percobaan, tidak ada batas jumlah attempt
     * untuk kind=quiz. Guard eligibility ditegakkan sebelum attempt baru dibuat.
     */
    public function startAttempt(User $user, QuizContent $quizContent): QuizAttempt
    {
        $this->guardEligibility($user, $quizContent);

        $nextAttemptNumber = 1 + (int) QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_content_id', $quizContent->id)
            ->max('attempt_number');

        return QuizAttempt::query()->create([
            'user_id' => $user->id,
            'quiz_content_id' => $quizContent->id,
            'attempt_number' => $nextAttemptNumber,
        ]);
    }

    /**
     * BR-06: skor tertinggi (persentase choice) yang dipakai untuk progres.
     */
    public function bestAttempt(User $user, QuizContent $quizContent): ?QuizAttempt
    {
        return QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_content_id', $quizContent->id)
            ->whereNotNull('completed_at')
            ->get()
            ->sortByDesc(fn (QuizAttempt $attempt): float => $attempt->choice_max_score > 0
                ? $attempt->choice_score / $attempt->choice_max_score
                : 0.0)
            ->first();
    }
}
