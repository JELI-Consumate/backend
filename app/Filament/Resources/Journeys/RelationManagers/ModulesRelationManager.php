<?php

declare(strict_types=1);

namespace App\Filament\Resources\Journeys\RelationManagers;

use App\Enums\ModuleType;
use App\Enums\PublishStatus;
use App\Filament\Resources\Modules\ModuleResource;
use App\Models\Scopes\Published;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $title = 'Modules';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->options(collect(ModuleType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                ->required(),
            TextInput::make('title')
                ->required()
                ->maxLength(200),
            Textarea::make('description'),
            TextInput::make('estimated_minutes')
                ->numeric()
                ->required()
                ->default(5),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
            Toggle::make('is_required')
                ->default(true),
            Select::make('status')
                ->options(collect(PublishStatus::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)]))
                ->required()
                ->default(PublishStatus::Draft->value),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScope(Published::class))
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('order')->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('estimated_minutes'),
                IconColumn::make('is_required')->boolean(),
                TextColumn::make('status')->badge(),
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
                EditAction::make()
                    ->url(fn ($record) => ModuleResource::getUrl('edit', ['record' => $record])),
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
