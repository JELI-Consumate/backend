<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LikertScaleOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['quiz_segment_id', 'value', 'label', 'order'])]
class LikertScaleOption extends Model
{
    /** @use HasFactory<LikertScaleOptionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<QuizSegment, $this>
     */
    public function quizSegment(): BelongsTo
    {
        return $this->belongsTo(QuizSegment::class);
    }
}
