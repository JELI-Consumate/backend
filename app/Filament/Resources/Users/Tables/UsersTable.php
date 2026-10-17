<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Exports\UserExporter;
use App\Filament\Support\AdminScope;
use App\Models\Sector;
use App\Models\SectorProgress;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    /**
     * Interval persen progres sektor, batas atas inklusif.
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private const PROGRESS_RANGES = [
        '0-25' => [0, 25],
        '26-50' => [26, 50],
        '51-75' => [51, 75],
        '76-100' => [76, 100],
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query, $livewire): Builder {
                $sectorId = self::activeSectorId($livewire->tableFilters['sector_progress'] ?? []);

                if ($sectorId === null) {
                    return $query;
                }

                return $query->addSelect([
                    'sector_progress_percent' => SectorProgress::query()
                        ->select('progress_percent')
                        ->whereColumn('user_id', 'users.id')
                        ->where('sector_id', $sectorId)
                        ->limit(1),
                ]);
            })
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sector_progress_percent')
                    ->label('Progres Sektor Ini')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state): string => $state === null ? '-' : "{$state}%"),
                TextColumn::make('sector_progress_count')
                    ->counts('sectorProgress')
                    ->label('Sektor Diikuti')
                    ->visible(fn (): bool => AdminScope::isSuperAdmin())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Terdaftar')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('sector_progress')
                    ->label('Progres Sektor')
                    ->schema([
                        Select::make('sector_id')
                            ->label('Sektor')
                            ->options(fn (): array => Sector::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->visible(fn (): bool => AdminScope::isSuperAdmin()),
                        Select::make('ranges')
                            ->label('Persentase penyelesaian')
                            ->multiple()
                            ->options([
                                '0-25' => '0–25%',
                                '26-50' => '26–50%',
                                '51-75' => '51–75%',
                                '76-100' => '76–100%',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $sectorId = self::activeSectorId($data);
                        $ranges = self::selectedRanges($data);

                        if ($sectorId === null || $ranges === []) {
                            return $query;
                        }

                        return $query->whereHas('sectorProgress', function (Builder $q) use ($sectorId, $ranges): void {
                            $q->where('sector_id', $sectorId)->where(function (Builder $q) use ($ranges): void {
                                foreach ($ranges as [$min, $max]) {
                                    $q->orWhereBetween('progress_percent', [$min, $max]);
                                }
                            });
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $ranges = array_keys(self::selectedRanges($data));

                        return $ranges === [] ? [] : ['Progres sektor: '.implode(', ', array_map(fn ($r) => str_replace('-', '–', $r).'%', $ranges))];
                    }),
            ])
            ->headerActions([
                ExportAction::make()->exporter(UserExporter::class),
            ])
            ->toolbarActions([
                ExportBulkAction::make()->exporter(UserExporter::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * Admin sektor: selalu sektornya sendiri. Super admin: sektor yang
     * dipilih di filter, atau null kalau belum memilih.
     *
     * @param  array<string, mixed>  $data
     */
    private static function activeSectorId(array $data): ?string
    {
        return AdminScope::restrictedSectorId() ?? ($data['sector_id'] ?? null) ?: null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array{0: int, 1: int}>
     */
    private static function selectedRanges(array $data): array
    {
        return array_intersect_key(self::PROGRESS_RANGES, array_flip((array) ($data['ranges'] ?? [])));
    }
}
