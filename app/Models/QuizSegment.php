<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuizSegmentType;
use Database\Factories\QuizSegmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['quiz_content_id', 'segment_type', 'title', 'instruction', 'order'])]
class QuizSegment extends Model
{
    /** @use HasFactory<QuizSegmentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'segment_type' => QuizSegmentType::class,
        ];
    }

    /**
     * @return BelongsTo<QuizContent, $this>
     */
    public function quizContent(): BelongsTo
    {
        return $this->belongsTo(QuizContent::class);
    }

    /**
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * @return HasMany<LikertScaleOption, $this>
     */
    public function likertScaleOptions(): HasMany
    {
        return $this->hasMany(LikertScaleOption::class)->orderBy('order');
    }
}
