<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReflectionChecklistAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tidak ada benar/salah — checklist cuma penanda personal user (sudah/belum
 * dicentang), tidak menghalangi completion module refleksi (BR-10).
 */
#[Fillable(['user_id', 'reflection_checklist_item_id', 'is_checked'])]
class ReflectionChecklistAnswer extends Model
{
    /** @use HasFactory<ReflectionChecklistAnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
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
     * @return BelongsTo<ReflectionChecklistItem, $this>
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ReflectionChecklistItem::class, 'reflection_checklist_item_id');
    }
}
