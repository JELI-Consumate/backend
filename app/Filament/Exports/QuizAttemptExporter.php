<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Filament\Support\AdminScope;
use App\Models\QuizAttempt;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu baris per (user, quiz_content) — attempt TERBAIK, sama logikanya
 * dengan QuizAttemptService::bestAttempt() (persentase choice_score/
 * choice_max_score tertinggi di antara attempt yang selesai). Self-contained:
 * dipakai baik lewat QuizAttemptsRelationManager (1 user) maupun tombol
 * "Export Data" (semua user sekaligus) — modifyQuery() di sini yang
 * menentukan baris "terbaik" itu, bukan menunggu filter dari luar.
 */
class QuizAttemptExporter extends Exporter
{
    protected static ?string $model = QuizAttempt::class;

    /**
     * Idempotent dengan sengaja: dipanggil manual dari QuizAttemptsRelationManager
     * (biar tabel & export pakai definisi "terbaik" yang sama persis), lalu
     * dipanggil LAGI otomatis oleh Filament setiap export jalan (lihat
     * CanExportRecords::modifyQuery di vendor) — tanpa guard ini, addSelect
     * di bawah bakal nempel dobel dan bikin SQL error duplicate alias.
     */
    public static function modifyQuery(Builder $query): Builder
    {
        $alreadyApplied = collect($query->getQuery()->columns ?? [])
            ->contains(fn ($column): bool => is_string($column) && str_contains($column, 'total_attempts'));

        if ($alreadyApplied) {
            return $query;
        }

        $bestAttemptIds = (clone $query)
            ->whereNotNull('completed_at')
            ->get(['id', 'user_id', 'quiz_content_id', 'choice_score', 'choice_max_score'])
            ->groupBy(fn (QuizAttempt $attempt): string => "{$attempt->user_id}|{$attempt->quiz_content_id}")
            ->map(fn ($attempts) => $attempts
                ->sortByDesc(fn (QuizAttempt $attempt): float => $attempt->choice_max_score > 0
                    ? $attempt->choice_score / $attempt->choice_max_score
                    : 0.0)
                ->first()
                ->id)
            ->values();

        $query->whereIn('quiz_attempts.id', $bestAttemptIds)
            ->whereHas('quizContent', fn (Builder $q) => AdminScope::scopeQuizContentSector($q->withoutGlobalScopes()))
            ->whereHas('user', fn (Builder $q) => $q->where('role', 'user'))
            ->with(['user', 'quizContent.journey', 'quizContent.sector'])
            ->addSelect([
                'total_attempts' => QuizAttempt::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('quiz_content_id', 'quiz_attempts.quiz_content_id')
                    ->whereColumn('user_id', 'quiz_attempts.user_id'),
                'last_attempt_at' => QuizAttempt::query()
                    ->selectRaw('max(completed_at)')
                    ->whereColumn('quiz_content_id', 'quiz_attempts.quiz_content_id')
                    ->whereColumn('user_id', 'quiz_attempts.user_id'),
            ]);

        return $query;
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')->label('Nama User'),
            ExportColumn::make('user.email')->label('Email User'),
            ExportColumn::make('quiz_name')
                ->label('Nama Kuis')
                ->state(fn (QuizAttempt $record): string => match ($record->quizContent->kind->value) {
                    'quiz' => $record->quizContent->journey->title.' (Kuis Journey)',
                    'pretest' => $record->quizContent->sector->name.' (Pretest)',
                    'posttest' => $record->quizContent->sector->name.' (Posttest)',
                }),
            ExportColumn::make('score')
                ->label('Skor Terbaik')
                ->state(fn (QuizAttempt $record): string => $record->choice_max_score > 0
                    ? "{$record->choice_score}/{$record->choice_max_score}"
                    : '-'),
            ExportColumn::make('total_attempts')->label('Total Attempt'),
            ExportColumn::make('passed')
                ->label('Lolos')
                ->state(fn (?bool $state): string => $state ? 'Lolos' : 'Belum Lolos'),
            ExportColumn::make('last_attempt_at')->label('Attempt Terakhir'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Export kuis & skor selesai, {$export->successful_rows} baris berhasil diexport.";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} baris gagal.";
        }

        return $body;
    }
}
