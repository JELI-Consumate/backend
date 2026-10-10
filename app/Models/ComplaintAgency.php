<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ComplaintAgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['slug', 'short_name', 'full_name', 'summary', 'description', 'when_to_contact', 'how_to_access', 'notes', 'logo_url', 'order', 'is_active'])]
class ComplaintAgency extends Model
{
    /** @use HasFactory<ComplaintAgencyFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'when_to_contact' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ComplaintAgencyChannel, $this>
     */
    public function channels(): HasMany
    {
        return $this->hasMany(ComplaintAgencyChannel::class)->orderBy('order');
    }

    /**
     * @return BelongsToMany<ComplaintCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ComplaintCategory::class, 'complaint_agency_category')
            ->withPivot('order');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
