<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SimulationMatchingAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['simulation_attempt_id', 'simulation_matching_pair_id', 'submitted_right_pair_id', 'is_correct'])]
class SimulationMatchingAnswer extends Model
{
    /** @use HasFactory<SimulationMatchingAnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SimulationAttempt, $this>
     */
    public function simulationAttempt(): BelongsTo
    {
        return $this->belongsTo(SimulationAttempt::class);
    }

    /**
     * @return BelongsTo<SimulationMatchingPair, $this>
     */
    public function matchingPair(): BelongsTo
    {
        return $this->belongsTo(SimulationMatchingPair::class, 'simulation_matching_pair_id');
    }

    /**
     * @return BelongsTo<SimulationMatchingPair, $this>
     */
    public function submittedRightPair(): BelongsTo
    {
        return $this->belongsTo(SimulationMatchingPair::class, 'submitted_right_pair_id');
    }
}
