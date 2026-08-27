<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sectors\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SectorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug())),
            TextInput::make('slug')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            TextInput::make('description')
                ->maxLength(1000),
            FileUpload::make('icon_url')
                ->image()
                ->maxSize(5120)
                ->directory('sectors/icons'),
            ColorPicker::make('color'),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
            Checkbox::make('is_active')
                ->default(true),
        ]);
    }
}
