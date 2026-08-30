<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DevicePlatform;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'fcm_token','platform'])]
class DeviceToken extends Model
{
    protected function casts(): array
    {
        return [
            'platform' => DevicePlatform::class,
        ];
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}