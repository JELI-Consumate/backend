<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Support\AdminScope;
use App\Models\QuizAttempt;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only, satu baris per quiz_content (bukan per attempt): kuis boleh
 * dicoba berkali-kali (BR-06) sehingga skor "berubah-ubah" per attempt —
 * baris di sini merepresentasikan attempt TERBAIK per quiz_content, sama
 * persis dengan logika QuizAttemptService::bestAttempt() (persentase
 * choice_score/choice_max_score tertinggi di antara attempt yang selesai).
 * Histori lengkap tiap attempt sengaja tidak ditampilkan di sini supaya
 * admin bisnis langsung dapat angka final tanpa perlu menghitung sendiri.
 */
class QuizAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizAttempts';

    protected static ?string $title = 'Kuis & Skor';

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = $this->getOwnerRecord();

        $bestAttemptIds = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->get(['id', 'quiz_content_id', 'choice_score', 'choice_max_score'])
            ->groupBy('quiz_content_id')
            ->map(fn ($attempts) => $attempts
                ->sortByDesc(fn (QuizAttempt $attempt): float => $attempt->choice_max_score > 0
                    ? $attempt->choice_score / $attempt->choice_max_score
                    : 0.0)
                ->first()
                ->id)
            ->values();

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($bestAttemptIds, $user): Builder {
                $query->whereIn('quiz_attempts.id', $bestAttemptIds)
                    ->addSelect([
                        'total_attempts' => QuizAttempt::query()
                            ->selectRaw('count(*)')
                            ->whereColumn('quiz_content_id', 'quiz_attempts.quiz_content_id')
                            ->where('user_id', $user->id),
                        'last_attempt_at' => QuizAttempt::query()
                            ->selectRaw('max(completed_at)')
                            ->whereColumn('quiz_content_id', 'quiz_attempts.quiz_content_id')
                            ->where('user_id', $user->id),
                    ]);

                $query->whereHas('quizContent', fn (Builder $q) => AdminScope::scopeQuizContentSector($q->withoutGlobalScopes()));

                return $query;
            })
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('quizContent.kind')
                    ->label('Nama Kuis')
                    ->state(fn (QuizAttempt $record): string => match ($record->quizContent->kind->value) {
                        'quiz' => $record->quizContent->journey->title.' (Kuis Journey)',
                        'pretest' => $record->quizContent->sector->name.' (Pretest)',
                        'posttest' => $record->quizContent->sector->name.' (Posttest)',
                    }),
                TextColumn::make('score')
                    ->label('Skor Terbaik')
                    ->state(fn (QuizAttempt $record): string => $record->choice_max_score > 0
                        ? "{$record->choice_score}/{$record->choice_max_score} (".intdiv($record->choice_score * 100, $record->choice_max_score).'%)'
                        : '-'),
                TextColumn::make('total_attempts')->label('Total Attempt'),
                TextColumn::make('passed')->label('Lolos')->badge()
                    ->formatStateUsing(fn (?bool $state): string => $state ? 'Lolos' : 'Belum Lolos')
                    ->color(fn (?bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('last_attempt_at')->label('Attempt Terakhir')->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
