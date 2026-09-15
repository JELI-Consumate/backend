<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Memastikan quiz attempt hanya bisa diakses oleh pemiliknya.
 */
final class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): Response
    {
        return $this->authorizeOwner($user, $attempt);
    }

    public function submit(User $user, QuizAttempt $attempt): Response
    {
        return $this->authorizeOwner($user, $attempt);
    }

    public function check(User $user, QuizAttempt $attempt): Response
    {
        return $this->authorizeOwner($user, $attempt);
    }

    private function authorizeOwner(User $user, QuizAttempt $attempt): Response
    {
        return $attempt->user_id === $user->id
            ? Response::allow()
            : Response::deny('Attempt ini bukan milik kamu.');
    }
}
