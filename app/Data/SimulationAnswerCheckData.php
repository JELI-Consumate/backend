<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SimulationType;

final readonly class SimulationAnswerCheckData
{
    public function __construct(
        public SimulationType $type,
        public ?int $simulationMatchingPairId = null,
        public ?int $submittedRightPairId = null,
        public ?int $simulationOrderingStepId = null,
        public ?int $submittedPosition = null,
    ) {}
}
