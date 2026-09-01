<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ModulePageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['module_id', 'order', 'contentable_type', 'contentable_id'])]
class ModulePage extends Model
{
    /** @use HasFactory<ModulePageFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function contentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<ModuleProgress, $this>
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(ModuleProgress::class);
    }
}
