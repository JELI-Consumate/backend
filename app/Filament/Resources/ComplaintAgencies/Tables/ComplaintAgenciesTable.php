<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComplaintAgencies\Tables;

use App\Filament\Support\AdminScope;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ComplaintAgenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')->sortable(),
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->disk(config('filament.default_filesystem_disk')),
                TextColumn::make('short_name')->label('Nama singkat')->searchable(),
                TextColumn::make('full_name')->label('Nama lengkap')->limit(50)->searchable(),
                TextColumn::make('channels_count')->counts('channels')->label('Kanal'),
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
