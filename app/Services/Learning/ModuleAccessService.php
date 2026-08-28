<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Enums\ProgressStatus;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\User;

final readonly class ModuleAccessService
{
    /**
     * Module dengan order terkecil di journey-nya selalu terbuka; module
     * berikutnya terbuka hanya kalau module sebelumnya (order - 1) di journey
     * yang sama sudah completed (SELURUH halamannya). Pola yang sama dengan
     * `JourneyAccessService::isUnlocked`, cuma di tingkat module dalam
     * journey, bukan journey dalam sektor.
     */
    public function isUnlocked(User $user, Module $module): bool
    {
        $previous = Module::query()
            ->where('journey_id', $module->journey_id)
            ->where('order', '<', $module->order)
            ->orderByDesc('order')
            ->first();

        if ($previous === null) {
            return true;
        }

        return $this->isModuleCompleted($user, $previous);
    }

    private function isModuleCompleted(User $user, Module $module): bool
    {
        $module->loadMissing('pages');
        $totalPages = $module->pages->count();

        // Tidak ada halaman sama sekali -> tidak ada yang bisa jadi penghalang,
        // dianggap "selesai" supaya module berikutnya tidak terkunci selamanya.
        if ($totalPages === 0) {
            return true;
        }

        $completedPages = ModuleProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('module_page_id', $module->pages->pluck('id'))
            ->where('status', ProgressStatus::Completed)
            ->count();

        return $completedPages >= $totalPages;
    }
}
