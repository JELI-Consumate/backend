<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SimulationAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class SimulationAttemptPolicy
{
    public function check(User $user, SimulationAttempt $attempt): Response
    {
        return $attempt->user_id === $user->id
            ? Response::allow()
            : Response::deny('Attempt ini bukan milik kamu.');
    }
}
