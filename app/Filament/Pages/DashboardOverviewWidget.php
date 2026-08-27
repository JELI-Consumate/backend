<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\AdminScope;
use App\Models\ArticleContent;
use App\Models\Journey;
use App\Models\Module;
use App\Models\QuizContent;
use App\Models\ReflectionContent;
use App\Models\Sector;
use App\Models\SimulationContent;
use App\Models\VideoContent;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ringkasan struktur konten di halaman awal panel (Dashboard) — beda dari
 * LearningAnalyticsStatsWidget yang fokus ke progres belajar pengguna,
 * ini fokus ke seberapa banyak konten yang sudah dibuat (draft + published,
 * bukan cuma yang tayang). Sengaja diletakkan di app/Filament/Pages (bukan
 * app/Filament/Widgets yang di-auto-discover) supaya cuma dipakai lewat
 * Dashboard::getWidgets(), bukan ikut ke resource lain.
 */
class DashboardOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $sectorId = AdminScope::restrictedSectorId();

        $sectorCount = Sector::query()
            ->when($sectorId, fn (Builder $query, int $sectorId) => $query->whereKey($sectorId))
            ->count();

        $journeyCount = Journey::withoutGlobalScopes()
            ->when($sectorId, fn (Builder $query, int $sectorId) => $query->where('sector_id', $sectorId))
            ->count();

        $moduleCount = Module::withoutGlobalScopes()
            ->when($sectorId, fn (Builder $query, int $sectorId) => $query->whereHas(
                'journey',
                fn (Builder $journeyQuery) => $journeyQuery->withoutGlobalScopes()->where('sector_id', $sectorId)
            ))
            ->count();

        $contentCount = AdminScope::scopeSectorContent(ArticleContent::query())->count()
            + AdminScope::scopeSectorContent(VideoContent::query())->count()
            + AdminScope::scopeSectorContent(SimulationContent::query())->count()
            + AdminScope::scopeSectorContent(ReflectionContent::query())->count()
            + AdminScope::scopeQuizContentSector(QuizContent::query())->count();

        return [
            Stat::make('Sector', (string) $sectorCount)
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('primary'),

            Stat::make('Journey', (string) $journeyCount)
                ->icon(Heroicon::OutlinedMap)
                ->color('primary'),

            Stat::make('Module', (string) $moduleCount)
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('primary'),

            Stat::make('Konten', (string) $contentCount)
                ->description('Article, Video, Quiz, Simulation, Reflection')
                ->icon(Heroicon::OutlinedFolder)
                ->color('primary'),
        ];
    }
}
