<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Enums\ProgressStatus;
use App\Models\ModulePage;
use App\Models\User;

final readonly class ProgressResolverService
{
    public function resolveNext(User $user): array
    {
        $page = ModulePage::query()
            ->select('module_pages.*')
            ->join('modules', 'modules.id', '=', 'module_pages.module_id')
            ->join('journeys', 'journeys.id', '=', 'modules.journey_id')
            ->join('sectors', 'sectors.id', '=', 'journeys.sector_id')
            ->where('sectors.is_active', true)
            ->whereDoesntHave('userProgress', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', ProgressStatus::Completed))
            ->orderBy('sectors.order')
            ->orderBy('journeys.order')
            ->orderBy('modules.order')
            ->orderBy('module_pages.order')
            ->with('module.journey')
            ->first();

        if ($page === null) {
            return [
                'sector_id' => null,
                'journey_id' => null,
                'module_page_id' => null,
            ];
        }

        return [
            'sector_id' => $page->module->journey->sector_id,
            'journey_id' => $page->module->journey_id,
            'module_page_id' => $page->id,
        ];
    }
}
