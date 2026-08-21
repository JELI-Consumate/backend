<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProgressStatus;
use Database\Factories\ModuleProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'module_page_id', 'status', 'last_position', 'completed_at'])]
class ModuleProgress extends Model
{
    /** @use HasFactory<ModuleProgressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'last_position' => 'integer',
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
     * @return BelongsTo<ModulePage, $this>
     */
    public function modulePage(): BelongsTo
    {
        return $this->belongsTo(ModulePage::class);
    }
}
