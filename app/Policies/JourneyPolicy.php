<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Journey;
use App\Models\User;
use App\Services\Learning\JourneyAccessService;

final readonly class JourneyPolicy
{
    public function __construct(private JourneyAccessService $access) {}

    /**
     * BR-01: journey hanya bisa dilihat kalau sudah terbuka.
     */
    public function view(User $user, Journey $journey): bool
    {
        return $this->access->isUnlocked($user, $journey);
    }
}
