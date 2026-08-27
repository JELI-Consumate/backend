<?php

declare(strict_types=1);

namespace App\Filament\Resources\SimulationContents\Tables;

use App\Filament\Resources\SimulationContents\Schemas\SimulationContentPreview;
use App\Filament\Support\ContentHierarchyOrder;
use App\Filament\Support\ParentContextColumns;
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
use Illuminate\Database\Eloquent\Builder;

class SimulationContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['matchingPairs', 'orderingSteps']))
            ->columns([
                TextColumn::make('title')->searchable(),
                ...ParentContextColumns::forModulePage(),
                TextColumn::make('simulation_type')->badge(),
            ])
            ->defaultSort(fn (Builder $query): Builder => ContentHierarchyOrder::apply($query))
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
