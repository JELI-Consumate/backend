<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SimulationOrderingStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['simulation_content_id', 'label', 'image_url', 'correct_position', 'order'])]
class SimulationOrderingStep extends Model
{
    /** @use HasFactory<SimulationOrderingStepFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<SimulationContent, $this>
     */
    public function simulationContent(): BelongsTo
    {
        return $this->belongsTo(SimulationContent::class);
    }
}
