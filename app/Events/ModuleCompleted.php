<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ModulePage;
use App\Models\User;

/**
 * Fire setelah ProgressService::markPageCompleted() sukses. $page sudah
 * loadMissing('module.journey') sebelum event ini dibuat, supaya listener
 * bisa akses $page->module->journey tanpa lazy load.
 */
final readonly class ModuleCompleted
{
    public function __construct(
        public User $user,
        public ModulePage $page,
    ) {}
}
