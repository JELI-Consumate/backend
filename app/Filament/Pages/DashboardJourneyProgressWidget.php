<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\ProgressStatus;
use App\Filament\Support\AdminScope;
use App\Models\Journey;
use App\Models\JourneyProgress;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tingkat penyelesaian tiap journey — versi ringkas dari
 * LearningAnalyticsService::journeyCompletion() untuk halaman awal, biar
 * admin langsung lihat journey mana yang macet tanpa buka Learning
 * Analytics. Dihitung per baris (bukan lewat withCount) karena Journey
 * belum punya relasi HasMany ke JourneyProgress — jumlah journey per sector
 * kecil (puluhan), jadi query per baris masih murah.
 */
class DashboardJourneyProgressWidget extends TableWidget
{
    protected int|string|array $columnSpan = 2;

    /**
     * @var array<string, array{total: int, percent: int}>
     */
    private array $progressByJourneyId = [];

    public function table(Table $table): Table
    {
        $sectorId = AdminScope::restrictedSectorId();

        return $table
            ->heading('Penyelesaian Journey')
            ->query(
                Journey::withoutGlobalScopes()
                    ->with('sector')
                    ->when($sectorId, fn (Builder $query, string $sectorId) => $query->where('sector_id', $sectorId))
            )
            ->columns([
                TextColumn::make('sector.name')->label('Sector'),
                TextColumn::make('title')->label('Journey')->searchable(),
                TextColumn::make('total_users')
                    ->label('Total Peserta')
                    ->state(fn (Journey $record) => $this->progressFor($record)['total']),
                TextColumn::make('completion')
                    ->label('Selesai')
                    ->state(fn (Journey $record) => $this->progressFor($record)['percent'].'%')
                    ->badge()
                    ->color(function (Journey $record): string {
                        $percent = $this->progressFor($record)['percent'];

                        return match (true) {
                            $percent >= 70 => 'success',
                            $percent >= 40 => 'warning',
                            default => 'danger',
                        };
                    }),
            ])
            ->paginated(false)
            ->defaultSort('title');
    }

    /**
     * @return array{total: int, percent: int}
     */
    private function progressFor(Journey $journey): array
    {
        return $this->progressByJourneyId[$journey->id] ??= (function () use ($journey): array {
            $total = JourneyProgress::query()->where('journey_id', $journey->id)->count();
            $completed = JourneyProgress::query()
                ->where('journey_id', $journey->id)
                ->where('status', ProgressStatus::Completed)
                ->count();

            return [
                'total' => $total,
                'percent' => $total > 0 ? (int) round($completed * 100 / $total) : 0,
            ];
        })();
    }
}
