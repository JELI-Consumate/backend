<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Filament\Support\AdminScope;
use App\Models\SectorProgress;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class SectorProgressExporter extends Exporter
{
    protected static ?string $model = SectorProgress::class;

    /**
     * Self-contained: dipakai baik lewat SectorProgressRelationManager
     * (1 user) maupun tombol "Export Data" (semua user sekaligus).
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return AdminScope::scopeSectorColumn($query)
            ->whereHas('user', fn (Builder $q) => $q->where('role', 'user'))
            ->with(['sector', 'user']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')->label('Nama User'),
            ExportColumn::make('user.email')->label('Email User'),
            ExportColumn::make('sector.name')->label('Sektor'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('progress_percent')->label('Persen'),
            ExportColumn::make('completed_at')->label('Completed At'),
            ExportColumn::make('pretest_survey_completed_at')->label('Pretest (survei)'),
            ExportColumn::make('posttest_survey_completed_at')->label('Posttest (survei)'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Export progres sektor selesai, {$export->successful_rows} baris berhasil diexport.";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} baris gagal.";
        }

        return $body;
    }
}
