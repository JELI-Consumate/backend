<?php

declare(strict_types=1);

namespace App\Filament\Resources\ArticleContents\Tables;

use App\Filament\Resources\ArticleContents\Schemas\ArticleContentPreview;
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

class ArticleContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('blocks'))
            ->columns([
                TextColumn::make('title')->searchable(),
                ...ParentContextColumns::forModulePage(),
                TextColumn::make('blocks_count')->counts('blocks')->label('Blocks'),
            ])
            // Urutan default ikut hierarki Sector -> Journey -> Module (lihat
            // ContentHierarchyOrder); konten yang belum ditempel ke module
            // manapun ditaruh paling akhir. Tetap kalah prioritas dari sort
            // manual kalau admin klik header kolom lain.
            ->defaultSort(fn (Builder $query): Builder => ContentHierarchyOrder::apply($query))
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading('Preview Artikel')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->schema(ArticleContentPreview::components()),
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
