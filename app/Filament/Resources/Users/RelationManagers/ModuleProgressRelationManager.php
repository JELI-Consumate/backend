<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Exports\ModuleProgressExporter;
use Filament\Actions\ExportAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only, drill-down level halaman modul (BR-11) — dipakai admin bisnis
 * untuk melihat halaman mana persisnya yang macet, bukan cuma persen journey.
 */
class ModuleProgressRelationManager extends RelationManager
{
    protected static string $relationship = 'moduleProgress';

    protected static ?string $title = 'Progres Modul';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => ModuleProgressExporter::modifyQuery($query))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('modulePage.module.journey.title')->label('Journey'),
                TextColumn::make('modulePage.module.title')->label('Module'),
                TextColumn::make('modulePage.order')->label('Halaman ke-'),
                TextColumn::make('status')->badge(),
                TextColumn::make('completed_at')->dateTime(),
            ])
            ->headerActions([
                ExportAction::make()->exporter(ModuleProgressExporter::class),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
