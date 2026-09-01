<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReflectionEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'reflection_question_id', 'answer_text'])]
class ReflectionEntry extends Model
{
    /** @use HasFactory<ReflectionEntryFactory> */
    use HasFactory, HasUlids;

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
