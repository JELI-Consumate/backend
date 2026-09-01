<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SimulationOrderingAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['simulation_attempt_id', 'simulation_ordering_step_id', 'submitted_position', 'is_correct'])]
class SimulationOrderingAnswer extends Model
{
    /** @use HasFactory<SimulationOrderingAnswerFactory> */
    use HasFactory, HasUlids;

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
     * @return BelongsTo<SimulationOrderingStep, $this>
     */
    public function orderingStep(): BelongsTo
    {
        return $this->belongsTo(SimulationOrderingStep::class, 'simulation_ordering_step_id');
    }
}
