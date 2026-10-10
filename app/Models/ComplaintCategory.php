<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ComplaintCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['problem', 'order', 'is_active'])]
class ComplaintCategory extends Model
{
    /** @use HasFactory<ComplaintCategoryFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<ComplaintAgency, $this>
     */
    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(ComplaintAgency::class, 'complaint_agency_category')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
