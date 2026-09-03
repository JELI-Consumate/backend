<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Filament\Support\AdminScope;
use App\Models\JourneyProgress;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class JourneyProgressExporter extends Exporter
{
    protected static ?string $model = JourneyProgress::class;

    /**
     * Self-contained: dipakai baik lewat JourneyProgressRelationManager
     * (1 user) maupun tombol "Export Data" (semua user sekaligus).
     */
    public static function modifyQuery(Builder $query): Builder
    {
        if ($sectorId = AdminScope::restrictedSectorId()) {
            $query->whereHas('journey', fn (Builder $q) => $q->withoutGlobalScopes()->where('sector_id', $sectorId));
        }

        return $query
            ->whereHas('user', fn (Builder $q) => $q->where('role', 'user'))
            ->with(['journey.sector', 'user']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')->label('Nama User'),
            ExportColumn::make('user.email')->label('Email User'),
            ExportColumn::make('journey.title')->label('Journey'),
            ExportColumn::make('journey.sector.name')->label('Sektor'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('progress_percent')->label('Persen'),
            ExportColumn::make('completed_at')->label('Completed At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Export progres journey selesai, {$export->successful_rows} baris berhasil diexport.";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} baris gagal.";
        }

        return $body;
    }
}
