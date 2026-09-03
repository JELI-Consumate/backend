<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\QuizAttempt;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ringkasan 1 baris per user, dipakai admin bisnis buat rekap cepat. Sengaja
 * tidak menyertakan avatar_url — file export tidak untuk membawa foto.
 */
class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['sectorProgress.sector', 'badges', 'quizAttempts']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('sectors')
                ->label('Sektor Diikuti')
                ->state(fn (User $record): string => $record->sectorProgress->pluck('sector.name')->filter()->implode(', ')),
            ExportColumn::make('overall_progress')
                ->label('Progres Keseluruhan (%)')
                ->state(fn (User $record): int => $record->sectorProgress->isEmpty()
                    ? 0
                    : intdiv((int) $record->sectorProgress->sum('progress_percent'), $record->sectorProgress->count())),
            ExportColumn::make('badges_count')->counts('badges')->label('Jumlah Badge'),
            ExportColumn::make('quiz_pass_rate')
                ->label('Quiz Pass Rate (%)')
                ->state(function (User $record): string {
                    $bestPerQuiz = $record->quizAttempts
                        ->whereNotNull('completed_at')
                        ->groupBy('quiz_content_id')
                        ->map(fn ($attempts) => $attempts
                            ->sortByDesc(fn (QuizAttempt $attempt): float => $attempt->choice_max_score > 0
                                ? $attempt->choice_score / $attempt->choice_max_score
                                : 0.0)
                            ->first());

                    if ($bestPerQuiz->isEmpty()) {
                        return '-';
                    }

                    return (string) intdiv($bestPerQuiz->where('passed', true)->count() * 100, $bestPerQuiz->count());
                }),
            ExportColumn::make('email_verified_at')
                ->label('Status Verifikasi')
                ->state(fn (User $record): string => $record->email_verified_at ? 'Terverifikasi' : 'Belum Terverifikasi'),
            ExportColumn::make('created_at')->label('Terdaftar'),
            ExportColumn::make('last_active_at')->label('Terakhir Aktif'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Export data user selesai, {$export->successful_rows} baris berhasil diexport.";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' baris gagal.';
        }

        return $body;
    }
}
