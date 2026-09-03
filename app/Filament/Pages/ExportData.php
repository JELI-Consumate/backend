<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Exports\JourneyProgressExporter;
use App\Filament\Exports\ModuleProgressExporter;
use App\Filament\Exports\QuizAttemptExporter;
use App\Filament\Exports\SectorProgressExporter;
use App\Filament\Support\AdminScope;
use BackedEnum;
use Filament\Actions\ExportAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Rekap detail lintas SEMUA user sekaligus — beda dari tab per-user di View
 * User (yang cuma export 1 orang). Tiap tombol pakai Exporter yang sama
 * persis dengan tab View User (lihat modifyQuery() di masing-masing
 * Exporter), jadi definisi datanya konsisten di kedua tempat.
 */
class ExportData extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Export Data';

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna & Analitik';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.export-data';

    public function getHeading(): string
    {
        return 'Export Data';
    }

    public function getSubheading(): ?string
    {
        return AdminScope::isSuperAdmin()
            ? 'Rekap lintas semua user, semua sector'
            : 'Rekap lintas semua user di sector kamu';
    }

    /**
     * Action beneran tetap didaftarkan di sini (supaya ke-cache & bisa
     * di-mount lewat wire:click="mountAction(...)" dari kartu di
     * resources/views/filament/pages/export-data.blade.php), tapi
     * getCachedHeaderActions() di-override jadi kosong di bawah supaya
     * tidak dobel dirender sebagai tombol pojok kanan atas.
     */
    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make('exportSectorProgress')
                ->label('Progres Sektor')
                ->exporter(SectorProgressExporter::class),
            ExportAction::make('exportJourneyProgress')
                ->label('Progres Journey')
                ->exporter(JourneyProgressExporter::class),
            ExportAction::make('exportModuleProgress')
                ->label('Progres Modul')
                ->exporter(ModuleProgressExporter::class),
            ExportAction::make('exportQuizAttempts')
                ->label('Kuis & Skor')
                ->exporter(QuizAttemptExporter::class),
        ];
    }

    public function getCachedHeaderActions(): array
    {
        return [];
    }
}
