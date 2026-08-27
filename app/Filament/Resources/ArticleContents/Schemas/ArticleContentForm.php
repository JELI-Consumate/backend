<?php

declare(strict_types=1);

namespace App\Filament\Resources\ArticleContents\Schemas;

use App\Enums\ArticleBlockType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(200),
            Repeater::make('blocks')
                ->relationship()
                ->orderColumn('order')
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['block_type'] ?? null)
                ->components([
                    Select::make('block_type')
                        ->options(collect(ArticleBlockType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value]))
                        ->live()
                        ->required(),
                    Textarea::make('text_article')
                        ->label('Teks')
                        ->visible(fn ($get) => in_array($get('block_type'), [
                            ArticleBlockType::Paragraph->value,
                            ArticleBlockType::ListItem->value,
                            ArticleBlockType::Reference->value,
                        ]))
                        ->requiredIf('block_type', [
                            ArticleBlockType::Paragraph->value,
                            ArticleBlockType::ListItem->value,
                            ArticleBlockType::Reference->value,
                        ]),
                    FileUpload::make('image_url')
                        ->image()
                        ->maxSize(5120)
                        ->directory('articles/blocks')
                        ->visible(fn ($get) => $get('block_type') === ArticleBlockType::Image->value)
                        ->requiredIf('block_type', ArticleBlockType::Image->value),
                    TextInput::make('alt_text')
                        ->label('Alt Text')
                        ->visible(fn ($get) => $get('block_type') === ArticleBlockType::Image->value)
                        ->requiredIf('block_type', ArticleBlockType::Image->value),
                ]),
        ]);
    }
}
