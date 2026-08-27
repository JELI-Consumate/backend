<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoContents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Component;

class VideoContentPreview
{
    /**
     * @return array<Component>
     */
    public static function components(): array
    {
        return [
            TextEntry::make('title'),
            TextEntry::make('description')->visible(fn ($record) => filled($record->description)),
            ViewEntry::make('youtube_embed')
                ->label('Preview')
                ->view('filament.infolists.video-embed'),
            TextEntry::make('prompt_question')->label('Pertanyaan Pemantik'),
        ];
    }
}
