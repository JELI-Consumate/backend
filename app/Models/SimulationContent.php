<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SimulationType;
use Database\Factories\SimulationContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'simulation_type', 'scenario'])]
class SimulationContent extends Model
{
    /** @use HasFactory<SimulationContentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'simulation_type' => SimulationType::class,
        ];
    }

    /**
     * @return MorphOne<ModulePage, $this>
     */
    public function modulePage(): MorphOne
    {
        return $this->morphOne(ModulePage::class, 'contentable');
    }

    /**
     * @return HasMany<SimulationMatchingPair, $this>
     */
    public function matchingPairs(): HasMany
    {
        return $this->hasMany(SimulationMatchingPair::class)->orderBy('order');
    }

    /**
     * @return HasMany<SimulationOrderingStep, $this>
     */
    public function orderingSteps(): HasMany
    {
        return $this->hasMany(SimulationOrderingStep::class)->orderBy('order');
    }
}
