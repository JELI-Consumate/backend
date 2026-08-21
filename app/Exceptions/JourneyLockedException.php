<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * BR-01: journey belum unlocked untuk user ini.
 */
final class JourneyLockedException extends RuntimeException
{
    public function __construct(string $message = 'Journey ini belum terbuka.')
    {
        parent::__construct($message);
    }
}
