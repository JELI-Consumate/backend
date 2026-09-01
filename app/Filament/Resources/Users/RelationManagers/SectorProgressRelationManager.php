<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Support\AdminScope;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only: progres dihasilkan otomatis oleh ProgressService, bukan diedit
 * manual lewat admin panel.
 */
class SectorProgressRelationManager extends RelationManager
{
    protected static string $relationship = 'sectorProgress';

    protected static ?string $title = 'Progres Sektor';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => AdminScope::scopeSectorColumn($query))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('sector.name')->label('Sektor'),
                TextColumn::make('status')->badge(),
                TextColumn::make('progress_percent')->label('Persen')->suffix('%'),
                TextColumn::make('completed_at')->dateTime(),
                TextColumn::make('pretest_survey_completed_at')
                    ->label('Pretest (survei)')
                    ->dateTime()
                    ->placeholder('Belum diisi'),
                TextColumn::make('posttest_survey_completed_at')
                    ->label('Posttest (survei)')
                    ->dateTime()
                    ->placeholder('Belum diisi'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
