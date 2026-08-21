<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SimulationAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'simulation_content_id', 'score', 'max_score', 'is_passed', 'completed_at'])]
class SimulationAttempt extends Model
{
    /** @use HasFactory<SimulationAttemptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_passed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SimulationContent, $this>
     */
    public function simulationContent(): BelongsTo
    {
        return $this->belongsTo(SimulationContent::class);
    }

    /**
     * @return HasMany<SimulationMatchingAnswer, $this>
     */
    public function matchingAnswers(): HasMany
    {
        return $this->hasMany(SimulationMatchingAnswer::class);
    }

    /**
     * @return HasMany<SimulationOrderingAnswer, $this>
     */
    public function orderingAnswers(): HasMany
    {
        return $this->hasMany(SimulationOrderingAnswer::class);
    }
}
