<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SimulationMatchingPairFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'simulation_content_id', 'left_label', 'left_description', 'left_image_url',
    'right_label', 'right_description', 'right_image_url', 'order',
])]
class SimulationMatchingPair extends Model
{
    /** @use HasFactory<SimulationMatchingPairFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return BelongsTo<SimulationContent, $this>
     */
    public function simulationContent(): BelongsTo
    {
        return $this->belongsTo(SimulationContent::class);
    }
}
