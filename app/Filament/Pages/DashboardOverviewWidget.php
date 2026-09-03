<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Support\AdminScope;
use App\Models\ArticleContent;
use App\Models\Journey;
use App\Models\Module;
use App\Models\QuizContent;
use App\Models\ReflectionContent;
use App\Models\Sector;
use App\Models\SimulationContent;
use App\Models\User;
use App\Models\VideoContent;
use App\Services\Analytics\LearningAnalyticsService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Kartu KPI di halaman awal panel (Dashboard) — gabungan ringkasan struktur
 * konten (draft + published) dan snapshot pengguna, biar admin langsung
 * dapat gambaran menyeluruh tanpa harus buka Learning Analytics dulu.
 * Sengaja diletakkan di app/Filament/Pages (bukan app/Filament/Widgets yang
 * di-auto-discover) supaya cuma dipakai lewat Dashboard::getWidgets().
 */
class DashboardOverviewWidget extends StatsOverviewWidget
{
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $sectorId = AdminScope::restrictedSectorId();
        $analytics = app(LearningAnalyticsService::class);

        $sectorCount = Sector::query()
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereKey($sectorId))
            ->count();

        $journeyCount = Journey::withoutGlobalScopes()
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->where('sector_id', $sectorId))
            ->count();

        $moduleCount = Module::withoutGlobalScopes()
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereHas(
                'journey',
                fn (Builder $journeyQuery) => $journeyQuery->withoutGlobalScopes()->where('sector_id', $sectorId)
            ))
            ->count();

        $contentCount = AdminScope::scopeSectorContent(ArticleContent::query())->count()
            + AdminScope::scopeSectorContent(VideoContent::query())->count()
            + AdminScope::scopeSectorContent(SimulationContent::query())->count()
            + AdminScope::scopeSectorContent(ReflectionContent::query())->count()
            + AdminScope::scopeQuizContentSector(QuizContent::query())->count();

        $totalUsers = $this->scopedUserQuery($sectorId)->count();
        $activeUsers = $analytics->activeUsersCount($sectorId);
        $registrationTrend = $this->dailyRegistrationTrend($sectorId, 7);

        return [
            Stat::make('Total Pengguna', (string) $totalUsers)
                ->description($totalUsers > 0 ? 'Terdaftar & terverifikasi' : 'Belum ada pengguna terdaftar')
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->icon(Heroicon::OutlinedUsers)
                ->chart($registrationTrend)
                ->chartColor('info')
                ->color('info'),

            Stat::make('User Aktif (30 hari)', (string) $activeUsers)
                ->description('Progres modul dalam 30 hari terakhir')
                ->descriptionIcon(Heroicon::OutlinedBolt)
                ->icon(Heroicon::OutlinedUserGroup)
                ->color($activeUsers > 0 ? 'success' : 'gray'),

            Stat::make('Konten', (string) $contentCount)
                ->description('Article, Video, Quiz, Simulation, Reflection')
                ->descriptionIcon(Heroicon::OutlinedFolder)
                ->icon(Heroicon::OutlinedFolder)
                ->color('warning'),

            Stat::make('Sector', (string) $sectorCount)
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('gray'),

            Stat::make('Journey', (string) $journeyCount)
                ->icon(Heroicon::OutlinedMap)
                ->color('gray'),

            Stat::make('Module', (string) $moduleCount)
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('gray'),
        ];
    }

    /**
     * @return Builder<User>
     */
    private function scopedUserQuery(?string $sectorId): Builder
    {
        return User::query()
            ->where('role', UserRole::User)
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereHas(
                'sectorProgress',
                fn (Builder $spq) => $spq->where('sector_id', $sectorId)
            ));
    }

    /**
     * @return array<int, int>
     */
    private function dailyRegistrationTrend(?string $sectorId, int $days): array
    {
        $since = Carbon::today()->subDays($days - 1);

        $counts = $this->scopedUserQuery($sectorId)
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, $days - 1))
            ->map(fn (int $offset) => (int) ($counts[$since->copy()->addDays($offset)->toDateString()] ?? 0))
            ->all();
    }
}
