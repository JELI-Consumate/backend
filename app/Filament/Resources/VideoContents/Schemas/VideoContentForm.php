<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoContents\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VideoContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(200),
            Textarea::make('description'),
            TextInput::make('youtube_url')
                ->label('URL YouTube')
                ->url()
                ->required(),
            Textarea::make('prompt_question')
                ->label('Pertanyaan Pemantik'),
        ]);
    }
}
