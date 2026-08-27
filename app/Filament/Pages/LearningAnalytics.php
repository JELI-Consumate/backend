<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\AdminScope;
use App\Models\Sector;
use App\Services\Analytics\LearningAnalyticsService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Halaman kustom "Pengguna & Analitik" (06-nonfunctional-ops.md §10): user
 * aktif, tingkat penyelesaian per journey, rata-rata skor & kelulusan kuis,
 * distribusi indeks keberdayaan. Query agregat sederhana (lihat
 * LearningAnalyticsService) — halaman ini dibuka jarang oleh peneliti,
 * bukan endpoint publik berbudget ketat seperti §8.
 *
 * Admin sector (lihat AdminScope) cuma melihat data sector-nya sendiri;
 * super admin melihat semua sector.
 */
class LearningAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Learning Analytics';

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna & Analitik';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.learning-analytics';

    /**
     * Nama sector kalau yang login admin sector (dipakai buat subjudul
     * halaman), null kalau super admin (tidak dibatasi).
     */
    public ?string $scopedSectorName = null;

    /** @var array<int, array{sector: string, title: string, total: int, completed: int, percent: int}> */
    public array $journeyCompletion = [];

    /** @var array{'0-25': int, '25-50': int, '50-75': int, '75-100': int} */
    public array $empowermentIndexDistribution = ['0-25' => 0, '25-50' => 0, '50-75' => 0, '75-100' => 0];

    public function mount(LearningAnalyticsService $analytics): void
    {
        $sectorId = AdminScope::restrictedSectorId();

        $this->scopedSectorName = $sectorId ? Sector::query()->find($sectorId)?->name : null;
        $this->journeyCompletion = $analytics->journeyCompletion($sectorId);
        $this->empowermentIndexDistribution = $analytics->empowermentIndexDistribution($sectorId);
    }

    public function getHeading(): string
    {
        return 'Learning Analytics';
    }

    public function getSubheading(): ?string
    {
        return $this->scopedSectorName
            ? "Data untuk sector: {$this->scopedSectorName}"
            : 'Data seluruh sector';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LearningAnalyticsStatsWidget::class,
        ];
    }

    /**
     * @return array<int, array{sector: string, journeys: array<int, array{title: string, total: int, completed: int, percent: int}>}>
     */
    public function getJourneyCompletionBySector(): array
    {
        $grouped = [];

        foreach ($this->journeyCompletion as $row) {
            $grouped[$row['sector']][] = $row;
        }

        return collect($grouped)
            ->map(fn (array $journeys, string $sector): array => ['sector' => $sector, 'journeys' => $journeys])
            ->values()
            ->all();
    }

    /**
     * Skala bar histogram distribusi: persentase terhadap bucket terbesar,
     * supaya bar-nya proporsional satu sama lain (bukan terhadap total).
     */
    public function getEmpowermentDistributionMax(): int
    {
        return max([1, ...array_values($this->empowermentIndexDistribution)]);
    }

    public function getEmpowermentDistributionTotal(): int
    {
        return array_sum($this->empowermentIndexDistribution);
    }

    /**
     * @param  array{title: string, total: int, completed: int, percent: int}  $journey
     */
    public function getCompletionBadgeColor(array $journey): string
    {
        if ($journey['total'] === 0) {
            return 'gray';
        }

        return match (true) {
            $journey['percent'] >= 70 => 'success',
            $journey['percent'] >= 40 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Warna satu hue makin gelap seiring makin tinggi rentang indeks —
     * memperkuat urutan "makin tinggi = makin gelap" (bukan warna acak per
     * bucket).
     */
    public function getEmpowermentBucketColor(string $bucket): string
    {
        return match ($bucket) {
            '0-25' => 'var(--primary-200)',
            '25-50' => 'var(--primary-400)',
            '50-75' => 'var(--primary-600)',
            '75-100' => 'var(--primary-800)',
            default => 'var(--primary-500)',
        };
    }
}
