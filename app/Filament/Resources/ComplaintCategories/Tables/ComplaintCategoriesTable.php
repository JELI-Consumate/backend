<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComplaintCategories\Tables;

use App\Filament\Support\AdminScope;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ComplaintCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')->sortable(),
                TextColumn::make('problem')->label('Permasalahan')->limit(80)->searchable(),
                TextColumn::make('agencies_count')->counts('agencies')->label('Lembaga'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order', AdminScope::isSuperAdmin())
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
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
