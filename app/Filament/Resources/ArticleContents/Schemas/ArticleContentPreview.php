<?php

declare(strict_types=1);

namespace App\Filament\Resources\ArticleContents\Schemas;

use App\Enums\ArticleBlockType;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;

class ArticleContentPreview
{
    /**
     * @return array<Component>
     */
    public static function components(): array
    {
        return [
            TextEntry::make('title'),
            RepeatableEntry::make('blocks')
                ->label('Blocks')
                ->schema([
                    TextEntry::make('block_type')->badge(),
                    TextEntry::make('text_article')
                        ->label('Teks')
                        ->markdown()
                        ->visible(fn ($record) => $record->block_type !== ArticleBlockType::Image),
                    ImageEntry::make('image_url')
                        ->label('Gambar')
                        ->visible(fn ($record) => $record->block_type === ArticleBlockType::Image),
                    TextEntry::make('alt_text')
                        ->visible(fn ($record) => $record->block_type === ArticleBlockType::Image),
                ]),
        ];
    }
}
