<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModuleType;
use App\Enums\PublishStatus;
use App\Models\Scopes\Published;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['journey_id', 'type', 'title', 'description', 'estimated_minutes', 'order', 'is_required', 'status'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new Published);
    }

    protected function casts(): array
    {
        return [
            'type' => ModuleType::class,
            'status' => PublishStatus::class,
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Journey, $this>
     */
    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    /**
     * @return HasMany<ModulePage, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(ModulePage::class);
    }
}
