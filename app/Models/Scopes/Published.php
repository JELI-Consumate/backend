<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * BR-02: hanya entitas berstatus published yang tampil di API publik.
 */
final class Published implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getTable().'.status', PublishStatus::Published->value);
    }
}
