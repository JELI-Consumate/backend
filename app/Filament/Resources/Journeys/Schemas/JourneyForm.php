<?php

declare(strict_types=1);

namespace App\Filament\Resources\Journeys\Schemas;

use App\Enums\PublishStatus;
use App\Models\Sector;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JourneyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sector_id')
                ->label('Sector')
                ->options(fn () => Sector::query()->pluck('name', 'id'))
                ->searchable()
                ->required(),
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
            TextInput::make('estimated_minutes')
                ->numeric()
                ->disabled()
                ->dehydrated(false)
                ->helperText('Turunan otomatis dari total durasi module published (BR-13).'),
            Select::make('status')
                ->options(collect(PublishStatus::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)]))
                ->required()
                ->default(PublishStatus::Draft->value),
        ]);
    }
}
