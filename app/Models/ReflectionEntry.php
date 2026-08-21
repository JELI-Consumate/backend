<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReflectionEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'reflection_question_id', 'answer_text', 'is_checked'])]
class ReflectionEntry extends Model
{
    /** @use HasFactory<ReflectionEntryFactory> */
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
     * @return BelongsTo<ReflectionQuestion, $this>
     */
    public function reflectionQuestion(): BelongsTo
    {
        return $this->belongsTo(ReflectionQuestion::class);
    }
}
