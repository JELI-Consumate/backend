<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SimulationSubmissionData
{
    /**
     * @param  array<int, array{simulation_matching_pair_id: int, submitted_right_pair_id: int}>  $matchingAnswers
     * @param  array<int, array{simulation_ordering_step_id: int, submitted_position: int}>  $orderingAnswers
     */
    public function __construct(
        public array $matchingAnswers,
        public array $orderingAnswers,
    ) {}
}
