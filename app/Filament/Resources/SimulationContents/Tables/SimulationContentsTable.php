<?php

declare(strict_types=1);

namespace App\Filament\Resources\SimulationContents\Tables;

use App\Filament\Resources\SimulationContents\Schemas\SimulationContentPreview;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SimulationContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['matchingPairs', 'orderingSteps']))
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('simulation_type')->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading('Preview Simulasi')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->schema(SimulationContentPreview::components()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
