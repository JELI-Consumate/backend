<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\AdminScope;
use App\Models\QuizAttempt;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Attempt kuis terbaru yang sudah selesai — sinyal aktivitas paling
 * "real-time" yang tersedia di data model kita untuk halaman awal panel,
 * dibanding kartu statis. Dibatasi sector yang sama seperti widget dashboard
 * lain lewat AdminScope::scopeQuizContentSector.
 */
class DashboardRecentActivityWidget extends TableWidget
{
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $sectorId = AdminScope::restrictedSectorId();

        return $table
            ->heading('Aktivitas Kuis Terbaru')
            ->query(
                QuizAttempt::query()
                    ->whereNotNull('completed_at')
                    ->with(['user', 'quizContent'])
                    ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereHas(
                        'quizContent',
                        fn (Builder $q) => $q->forSector($sectorId)
                    ))
            )
            ->columns([
                TextColumn::make('user.name')->label('Pengguna'),
                TextColumn::make('quizContent.kind')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state->value)),
                TextColumn::make('score')
                    ->label('Skor')
                    ->state(fn (QuizAttempt $record) => $record->choice_max_score > 0
                        ? round($record->choice_score * 100 / $record->choice_max_score).'%'
                        : '-'),
                IconColumn::make('passed')
                    ->label('Lulus')
                    ->boolean(),
                TextColumn::make('completed_at')
                    ->label('Waktu')
                    ->since(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5]);
    }
}
