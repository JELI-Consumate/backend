<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoContents\Tables;

use App\Filament\Resources\VideoContents\Schemas\VideoContentPreview;
use App\Filament\Support\ContentHierarchyOrder;
use App\Filament\Support\ParentContextColumns;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('description')->limit(50)->toggleable(),
                ...ParentContextColumns::forModulePage(),
                TextColumn::make('youtube_video_id')->label('Video ID'),
                ImageColumn::make('youtube_video_id')
                    ->label('Preview')
                    ->getStateUsing(fn ($record) => $record->youtube_video_id
                        ? "https://img.youtube.com/vi/{$record->youtube_video_id}/default.jpg"
                        : null),
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
                    ->modalHeading('Preview Video')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->schema(VideoContentPreview::components()),
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
