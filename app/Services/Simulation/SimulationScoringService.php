<?php

declare(strict_types=1);

namespace App\Services\Simulation;

use App\Data\SimulationSubmissionData;
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
     * BR-08: attempt yang sudah completed_at != null bersifat immutable.
     * Scoring matching: submitted_right_pair_id vs simulation_matching_pair_id.
     * Scoring ordering: submitted_position vs correct_position (preload 1 query).
     */
    public function submit(SimulationAttempt $attempt, SimulationSubmissionData $data): SimulationAttempt
    {
        if ($attempt->completed_at !== null) {
            throw new InvalidSubmissionException('Attempt sudah pernah diselesaikan.');
        }

        return DB::transaction(function () use ($attempt, $data): SimulationAttempt {
            $now = Date::now();

            $matchingRows = [];
            $matchingScore = 0;

            foreach ($data->matchingAnswers as $answer) {
                $isCorrect = $answer['submitted_right_pair_id'] === $answer['simulation_matching_pair_id'];
                $matchingScore += $isCorrect ? 1 : 0;

                $matchingRows[] = [
                    'simulation_attempt_id' => $attempt->id,
                    'simulation_matching_pair_id' => $answer['simulation_matching_pair_id'],
                    'submitted_right_pair_id' => $answer['submitted_right_pair_id'],
                    'is_correct' => $isCorrect,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Preload posisi benar — 1 query (pluck), bukan query per item.
            $correctPositionByStep = SimulationOrderingStep::query()
                ->whereIn('id', array_column($data->orderingAnswers, 'simulation_ordering_step_id'))
                ->pluck('correct_position', 'id');

            $orderingRows = [];
            $orderingScore = 0;

            foreach ($data->orderingAnswers as $answer) {
                $isCorrect = ($correctPositionByStep[$answer['simulation_ordering_step_id']] ?? null) === $answer['submitted_position'];
                $orderingScore += $isCorrect ? 1 : 0;

                $orderingRows[] = [
                    'simulation_attempt_id' => $attempt->id,
                    'simulation_ordering_step_id' => $answer['simulation_ordering_step_id'],
                    'submitted_position' => $answer['submitted_position'],
                    'is_correct' => $isCorrect,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($matchingRows !== []) {
                SimulationMatchingAnswer::insert($matchingRows);
            }

            if ($orderingRows !== []) {
                SimulationOrderingAnswer::insert($orderingRows);
            }

            $maxScore = count($data->matchingAnswers) + count($data->orderingAnswers);
            $score = $matchingScore + $orderingScore;

            $attempt->update([
                'score' => $score,
                'max_score' => $maxScore,
                'is_passed' => $maxScore > 0 && $score === $maxScore,
                'completed_at' => $now,
            ]);

            $attempt->simulationContent->loadMissing('modulePage');

            if ($attempt->simulationContent->modulePage !== null) {
                $this->progress->markPageCompleted($attempt->user, $attempt->simulationContent->modulePage);
            }

            return $attempt->fresh();
        });
    }
}
