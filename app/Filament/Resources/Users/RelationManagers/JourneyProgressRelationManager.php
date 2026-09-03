<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Exports\JourneyProgressExporter;
use Filament\Actions\ExportAction;
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
            ->modifyQueryUsing(fn (Builder $query): Builder => JourneyProgressExporter::modifyQuery($query))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('journey.title')->label('Journey'),
                TextColumn::make('journey.sector.name')->label('Sektor'),
                TextColumn::make('status')->badge(),
                TextColumn::make('progress_percent')->label('Persen')->suffix('%'),
                TextColumn::make('completed_at')->dateTime(),
            ])
            ->headerActions([
                ExportAction::make()->exporter(JourneyProgressExporter::class),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
