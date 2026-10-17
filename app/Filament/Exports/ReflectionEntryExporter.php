<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Filament\Support\AdminScope;
use App\Models\ModulePage;
use App\Models\ReflectionEntry;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu baris per jawaban teks refleksi. Hanya konten aktif: belum dihapus,
 * masih terpasang di modul, dan modul + journey published. Checklist tidak ikut.
 */
class ReflectionEntryExporter extends Exporter
{
    protected static ?string $model = ReflectionEntry::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query
            ->whereNotNull('reflection_entries.answer_text')
            ->where('reflection_entries.answer_text', '!=', '')
            ->whereHas('user', fn (Builder $q) => $q->where('role', 'user'))
            ->whereHas('reflectionQuestion.reflectionSection.reflectionContent', function (Builder $q): void {
                $q->whereHas('modulePage.module.journey');
                AdminScope::scopeSectorContent($q);
            })
            ->with([
                'user',
                'reflectionQuestion.reflectionSection.reflectionContent.modulePage.module.journey',
            ]);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')->label('Nama User'),
            ExportColumn::make('user.email')->label('Email User'),
            ExportColumn::make('journey')
                ->label('Journey')
                ->state(fn (ReflectionEntry $record): string => self::modulePage($record)?->module?->journey?->title ?? '-'),
            ExportColumn::make('module')
                ->label('Modul')
                ->state(fn (ReflectionEntry $record): string => self::modulePage($record)?->module?->title ?? '-'),
            ExportColumn::make('reflection_title')
                ->label('Judul Refleksi')
                ->state(fn (ReflectionEntry $record): string => $record->reflectionQuestion?->reflectionSection?->reflectionContent?->title ?? '-'),
            ExportColumn::make('section_title')
                ->label('Bagian')
                ->state(fn (ReflectionEntry $record): string => $record->reflectionQuestion?->reflectionSection?->title ?? '-'),
            ExportColumn::make('question_text')
                ->label('Pertanyaan')
                ->state(fn (ReflectionEntry $record): string => $record->reflectionQuestion?->question_text ?? '-'),
            ExportColumn::make('answer_text')->label('Jawaban'),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Export jawaban refleksi selesai, {$export->successful_rows} baris berhasil diexport.";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} baris gagal.";
        }

        return $body;
    }

    private static function modulePage(ReflectionEntry $record): ?ModulePage
    {
        return $record->reflectionQuestion?->reflectionSection?->reflectionContent?->modulePage;
    }
}
