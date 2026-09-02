<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Scopes\Published;
use Database\Factories\JourneyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sector_id', 'slug', 'title', 'description', 'image_url', 'order', 'status', 'published_at'])]
class Journey extends Model
{
    /** @use HasFactory<JourneyFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new Published);
    }

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Satu journey punya paling banyak satu badge (unique index di
     * `badges.journey_id`) -- diberikan otomatis lewat BadgeService begitu
     * journey ini selesai. Dipakai BadgeRelationManager di Filament.
     *
     * @return HasOne<Badge, $this>
     */
    public function badge(): HasOne
    {
        return $this->hasOne(Badge::class);
    }
}
