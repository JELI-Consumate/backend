<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ModulePage;

/**
 * Menyentuh module pemilik saat halaman ditambah/diurutkan ulang/dihapus,
 * supaya cache tree di ContentTreeService ikut invalid.
 */
final class ModulePageObserver
{
    public function saved(ModulePage $page): void
    {
        $page->module?->touch();
    }

    public function deleted(ModulePage $page): void
    {
        $page->module?->touch();
    }
}
