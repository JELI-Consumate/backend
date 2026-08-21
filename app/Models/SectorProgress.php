<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProgressStatus;
use Database\Factories\SectorProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'sector_id', 'status', 'progress_percent', 'completed_at'])]
class SectorProgress extends Model
{
    /** @use HasFactory<SectorProgressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'progress_percent' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
