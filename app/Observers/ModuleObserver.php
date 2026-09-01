<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Journey;
use App\Models\Module;
use App\Models\Scopes\Published;

/**
 * BR-13: journeys.estimated_minutes adalah kolom turunan dari SUM(modules.estimated_minutes)
 * untuk seluruh module published, direkalkulasi tiap module disimpan/dihapus.
 */
final class ModuleObserver
{
    public function saved(Module $module): void
    {
        $this->resync($module->journey_id);
    }

    public function deleted(Module $module): void
    {
        $this->resync($module->journey_id);
    }

    private function resync(string $journeyId): void
    {
        $total = Module::query()->where('journey_id', $journeyId)->sum('estimated_minutes');

        Journey::withoutGlobalScope(Published::class)
            ->where('id', $journeyId)
            ->update(['estimated_minutes' => $total]);
    }
}
