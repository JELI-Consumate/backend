<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Journey belum unlocked untuk user ini.
 */
final class JourneyLockedException extends RuntimeException
{
    public function __construct(string $message = 'Journey ini belum terbuka.')
    {
        parent::__construct($message);
    }
}
