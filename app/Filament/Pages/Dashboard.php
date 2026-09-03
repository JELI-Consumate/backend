<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\AdminScope;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Halaman awal panel admin — override Dashboard bawaan Filament supaya:
 * - sapaan disesuaikan dengan user & scope-nya (super admin / admin sector).
 * - widget-nya cuma ringkasan struktur konten (DashboardOverviewWidget),
 *   bukan AccountWidget/FilamentInfoWidget bawaan (kartu promosi Filament).
 */
class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        $name = AdminScope::user()?->name;

        return $name ? "Selamat datang, {$name}" : 'Selamat datang';
    }

    public function getSubheading(): ?string
    {
        if (AdminScope::isSuperAdmin()) {
            return 'Super Admin, akses semua sector';
        }

        $sectorName = AdminScope::user()?->sector?->name ?? '-';

        return "Admin Sector: {$sectorName}";
    }

    public function getColumns(): int|array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            DashboardOverviewWidget::class,
            DashboardRegistrationsChartWidget::class,
            DashboardContentBreakdownWidget::class,
            DashboardJourneyProgressWidget::class,
            DashboardRecentActivityWidget::class,
        ];
    }
}
