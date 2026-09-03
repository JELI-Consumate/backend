<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\AdminScope;
use App\Services\Analytics\LearningAnalyticsService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Kartu KPI di atas halaman Learning Analytics. Sengaja diletakkan di
 * app/Filament/Pages, bukan app/Filament/Widgets (yang di-auto-discover
 * lewat AdminPanelProvider::discoverWidgets), supaya tidak otomatis ikut
 * tampil di Dashboard utama — cuma dipakai lewat
 * LearningAnalytics::getHeaderWidgets().
 */
class LearningAnalyticsStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $analytics = app(LearningAnalyticsService::class);
        $sectorId = AdminScope::restrictedSectorId();

        $averageQuizScore = $analytics->averageQuizScore($sectorId);
        $quizPassRate = $analytics->quizPassRate($sectorId);

        return [
            Stat::make('User Aktif (30 hari)', (string) $analytics->activeUsersCount($sectorId))
                ->description('Progres modul dalam 30 hari terakhir')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->icon(Heroicon::OutlinedUsers)
                ->color('info'),

            Stat::make('Rata-rata Skor Kuis', $averageQuizScore === null ? '-' : "{$averageQuizScore}%")
                ->description($averageQuizScore === null ? 'Belum ada attempt selesai' : 'Semua attempt kuis yang sudah selesai')
                ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                ->icon(Heroicon::OutlinedChartBar)
                ->color(match (true) {
                    $averageQuizScore === null => 'gray',
                    $averageQuizScore >= 70 => 'success',
                    $averageQuizScore >= 50 => 'warning',
                    default => 'danger',
                }),

            Stat::make('Tingkat Kelulusan Kuis', $quizPassRate === null ? '-' : "{$quizPassRate}%")
                ->description($quizPassRate === null ? 'Belum ada attempt selesai' : 'Attempt yang lulus passing score')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color(match (true) {
                    $quizPassRate === null => 'gray',
                    $quizPassRate >= 70 => 'success',
                    $quizPassRate >= 40 => 'warning',
                    default => 'danger',
                }),
        ];
    }
}
