<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Support\AdminScope;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Tren pendaftaran pengguna 14 hari terakhir — dipisah dari
 * DashboardOverviewWidget (kartu KPI) supaya pertumbuhan pengguna terlihat
 * sebagai grafik, bukan cuma angka tunggal. Diletakkan di app/Filament/Pages
 * (bukan app/Filament/Widgets) dengan alasan sama seperti widget dashboard
 * lain: cuma dipakai lewat Dashboard::getWidgets().
 */
class DashboardRegistrationsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Pendaftaran Pengguna';

    protected ?string $description = '14 hari terakhir';

    protected int|string|array $columnSpan = 2;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $sectorId = AdminScope::restrictedSectorId();
        $days = 14;
        $since = Carbon::today()->subDays($days - 1);

        $counts = User::query()
            ->where('role', UserRole::User)
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereHas(
                'sectorProgress',
                fn (Builder $spq) => $spq->where('sector_id', $sectorId)
            ))
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];

        foreach (range(0, $days - 1) as $offset) {
            $date = $since->copy()->addDays($offset);
            $labels[] = $date->translatedFormat('d M');
            $data[] = (int) ($counts[$date->toDateString()] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendaftaran',
                    'data' => $data,
                    'fill' => true,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }
}
