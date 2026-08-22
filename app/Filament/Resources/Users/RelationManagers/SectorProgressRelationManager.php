<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('sector.name')->label('Sektor'),
                TextColumn::make('status')->badge(),
                TextColumn::make('progress_percent')->label('Persen')->suffix('%'),
                TextColumn::make('completed_at')->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
