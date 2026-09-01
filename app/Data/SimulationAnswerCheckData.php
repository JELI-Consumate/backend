<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SimulationType;

final readonly class SimulationAnswerCheckData
{
    public function __construct(
        public SimulationType $type,
        public ?string $simulationMatchingPairId = null,
        public ?string $submittedRightPairId = null,
        public ?string $simulationOrderingStepId = null,
        public ?int $submittedPosition = null,
    ) {}
}
