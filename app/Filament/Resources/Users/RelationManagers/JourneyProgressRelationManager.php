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
class JourneyProgressRelationManager extends RelationManager
{
    protected static string $relationship = 'journeyProgress';

    protected static ?string $title = 'Progres Journey';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if ($sectorId = AdminScope::restrictedSectorId()) {
                    $query->whereHas('journey', fn (Builder $q) => $q->withoutGlobalScopes()->where('sector_id', $sectorId));
                }

                return $query;
            })
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('journey.title')->label('Journey'),
                TextColumn::make('status')->badge(),
                TextColumn::make('progress_percent')->label('Persen')->suffix('%'),
                TextColumn::make('completed_at')->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
