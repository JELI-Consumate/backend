<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Module ini belum unlocked untuk user ini -- module sebelumnya (order - 1)
 * di journey yang sama belum completed. Lihat `ModuleAccessService`.
 */
final class ModuleLockedException extends RuntimeException
{
    public function __construct(string $message = 'Selesaikan modul sebelumnya terlebih dahulu.')
    {
        parent::__construct($message);
    }
}
