<?php

declare(strict_types=1);

namespace App\Filament\Resources\Journeys\Tables;

use App\Enums\PublishStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class JourneysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sector.name')->sortable(),
                TextColumn::make('order')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('estimated_minutes')->label('Durasi (menit)'),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->visible(fn ($record) => $record->status !== PublishStatus::Published)
                    ->action(fn ($record) => $record->update(['status' => PublishStatus::Published, 'published_at' => now()])),
                Action::make('unpublish')
                    ->visible(fn ($record) => $record->status === PublishStatus::Published)
                    ->action(fn ($record) => $record->update(['status' => PublishStatus::Draft])),
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
