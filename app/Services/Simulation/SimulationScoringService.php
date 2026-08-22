<?php

declare(strict_types=1);

namespace App\Services\Simulation;

use App\Data\SimulationAnswerCheckData;
use App\Data\SimulationAnswerCheckResult;
use App\Enums\SimulationType;
use App\Exceptions\InvalidSubmissionException;
use App\Exceptions\JourneyLockedException;
use App\Models\SimulationAttempt;
use App\Models\SimulationContent;
use App\Models\SimulationMatchingAnswer;
use App\Models\SimulationOrderingAnswer;
use App\Models\SimulationOrderingStep;
use App\Models\User;
use App\Services\Learning\JourneyAccessService;
use App\Services\Progress\ProgressService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class SimulationScoringService
{
    public function __construct(
        private ProgressService $progress,
        private JourneyAccessService $journeyAccess,
    ) {}

    public function startAttempt(User $user, SimulationContent $content): SimulationAttempt
    {
        $content->loadMissing('modulePage.module.journey');

        if ($content->modulePage !== null && ! $this->journeyAccess->isUnlocked($user, $content->modulePage->module->journey)) {
            throw new JourneyLockedException;
        }

        return SimulationAttempt::query()->create([
            'user_id' => $user->id,
            'simulation_content_id' => $content->id,
        ]);
    }

    /**
     * Duolingo-style: cek SATU item per panggilan. Jawaban salah ditolak
     * (`correct=false`) dan TIDAK disimpan — user boleh coba lagi item yang
     * sama tanpa attempt-nya berubah status. Jawaban benar disimpan idempotent
     * (aman dipanggil ulang untuk item yang sama). Attempt otomatis completed
     * (BR-08: immutable setelahnya) begitu seluruh item simulasi ini sudah
     * pernah dijawab benar — tidak ada lagi konsep "submit gagal/lulus
     * sebagian", karena satu-satunya cara selesai adalah menjawab semua benar.
     */
    public function checkAnswer(SimulationAttempt $attempt, SimulationAnswerCheckData $data): SimulationAnswerCheckResult
    {
        if ($attempt->completed_at !== null) {
            throw new InvalidSubmissionException('Attempt sudah pernah diselesaikan.');
        }

        return DB::transaction(function () use ($attempt, $data): SimulationAnswerCheckResult {
            $isCorrect = match ($data->type) {
                SimulationType::Matching => $data->submittedRightPairId === $data->simulationMatchingPairId,
                SimulationType::Ordering => $this->correctPosition($data->simulationOrderingStepId) === $data->submittedPosition,
            };

            if (! $isCorrect) {
                return new SimulationAnswerCheckResult(correct: false, attempt: $attempt);
            }

            match ($data->type) {
                SimulationType::Matching => SimulationMatchingAnswer::query()->firstOrCreate(
                    [
                        'simulation_attempt_id' => $attempt->id,
                        'simulation_matching_pair_id' => $data->simulationMatchingPairId,
                    ],
                    ['submitted_right_pair_id' => $data->submittedRightPairId, 'is_correct' => true],
                ),
                SimulationType::Ordering => SimulationOrderingAnswer::query()->firstOrCreate(
                    [
                        'simulation_attempt_id' => $attempt->id,
                        'simulation_ordering_step_id' => $data->simulationOrderingStepId,
                    ],
                    ['submitted_position' => $data->submittedPosition, 'is_correct' => true],
                ),
            };

            $this->completeIfAllAnswered($attempt);

            return new SimulationAnswerCheckResult(correct: true, attempt: $attempt->fresh());
        });
    }

    private function correctPosition(?int $stepId): ?int
    {
        if ($stepId === null) {
            return null;
        }

        return SimulationOrderingStep::query()->where('id', $stepId)->value('correct_position');
    }

    private function completeIfAllAnswered(SimulationAttempt $attempt): void
    {
        $attempt->loadCount(['matchingAnswers', 'orderingAnswers']);
        $attempt->simulationContent->loadCount(['matchingPairs', 'orderingSteps']);

        $totalItems = $attempt->simulationContent->matching_pairs_count + $attempt->simulationContent->ordering_steps_count;
        $answeredItems = $attempt->matching_answers_count + $attempt->ordering_answers_count;

        if ($totalItems === 0 || $answeredItems < $totalItems) {
            return;
        }

        $attempt->update([
            'score' => $totalItems,
            'max_score' => $totalItems,
            'is_passed' => true,
            'completed_at' => Date::now(),
        ]);

        $attempt->simulationContent->loadMissing('modulePage');

        if ($attempt->simulationContent->modulePage !== null) {
            $this->progress->markPageCompleted($attempt->user, $attempt->simulationContent->modulePage);
        }
    }
}
