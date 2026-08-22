<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sectors\RelationManagers;

use App\Enums\PublishStatus;
use App\Filament\Resources\Journeys\JourneyResource;
use App\Models\Scopes\Published;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class JourneysRelationManager extends RelationManager
{
    protected static string $relationship = 'journeys';

    protected static ?string $title = 'Journeys';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(200)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug())),
            TextInput::make('slug')
                ->required()
                ->maxLength(100),
            Textarea::make('description'),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScope(Published::class))
            ->recordTitleAttribute('title')
            ->columns([
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
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->visible(fn ($record) => $record->status !== PublishStatus::Published)
                    ->action(fn ($record) => $record->update(['status' => PublishStatus::Published, 'published_at' => now()])),
                Action::make('unpublish')
                    ->visible(fn ($record) => $record->status === PublishStatus::Published)
                    ->action(fn ($record) => $record->update(['status' => PublishStatus::Draft])),
                EditAction::make()
                    ->url(fn ($record) => JourneyResource::getUrl('edit', ['record' => $record])),
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
