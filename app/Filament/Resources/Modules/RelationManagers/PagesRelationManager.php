<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Enums\ContentableType;
use App\Models\ArticleContent;
use App\Models\QuizContent;
use App\Models\ReflectionContent;
use App\Models\SimulationContent;
use App\Models\VideoContent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    private static function contentModel(?string $type): ?string
    {
        return match ($type) {
            ContentableType::Video->value => VideoContent::class,
            ContentableType::Article->value => ArticleContent::class,
            ContentableType::Quiz->value => QuizContent::class,
            ContentableType::Simulation->value => SimulationContent::class,
            ContentableType::Reflection->value => ReflectionContent::class,
            default => null,
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('contentable_type')
                ->label('Tipe Konten')
                ->options(collect(ContentableType::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)]))
                ->live()
                ->required(),
            Select::make('contentable_id')
                ->label('Konten')
                ->options(function ($get) {
                    $model = self::contentModel($get('contentable_type'));

                    return $model ? $model::query()->pluck('title', 'id') : [];
                })
                ->searchable()
                ->required(),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order')
            ->columns([
                TextColumn::make('order')->sortable(),
                TextColumn::make('contentable_type')->label('Tipe'),
                TextColumn::make('contentable.title')->label('Judul Konten'),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
