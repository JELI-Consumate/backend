<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BadgeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['journey_id', 'name', 'description', 'congratulation_message', 'motivational_message', 'icon_url'])]
class Badge extends Model
{
    /** @use HasFactory<BadgeFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return BelongsTo<Journey, $this>
     */
    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    /**
     * @return HasMany<UserBadge, $this>
     */
    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
