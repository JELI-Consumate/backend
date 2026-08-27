<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuizKind;
use Database\Factories\QuizContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kind', 'journey_id', 'sector_id', 'passing_score', 'shuffle_questions'])]
class QuizContent extends Model
{
    /** @use HasFactory<QuizContentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'kind' => QuizKind::class,
            'shuffle_questions' => 'boolean',
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
     * @return BelongsTo<Journey, $this>
     */
    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return HasMany<QuizSegment, $this>
     */
    public function segments(): HasMany
    {
        return $this->hasMany(QuizSegment::class)->orderBy('order');
    }

    /**
     * QuizContent terhubung ke sector lewat dua jalur: langsung via
     * sector_id (kind pretest/posttest) atau tidak langsung via journey_id
     * (kind quiz). Dipakai bareng oleh AdminScope (panel) & analitik.
     *
     * @param  Builder<QuizContent>  $query
     * @return Builder<QuizContent>
     */
    public function scopeForSector(Builder $query, int $sectorId): Builder
    {
        return $query->where(function (Builder $q) use ($sectorId): void {
            $q->where('sector_id', $sectorId)
                ->orWhereHas(
                    'journey',
                    fn (Builder $journeyQuery) => $journeyQuery->withoutGlobalScopes()->where('sector_id', $sectorId)
                );
        });
    }
}
