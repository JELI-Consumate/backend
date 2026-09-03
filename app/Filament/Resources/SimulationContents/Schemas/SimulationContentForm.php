<?php

declare(strict_types=1);

namespace App\Filament\Resources\SimulationContents\Schemas;

use App\Enums\SimulationType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SimulationContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')
                ->required()
                ->maxLength(200),
            Select::make('simulation_type')
                ->options(collect(SimulationType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value]))
                ->live()
                ->required(),
            Textarea::make('scenario')
                ->required()
                ->columnSpanFull(),
            Section::make('Matching Pairs')
                ->columnSpanFull()
                ->visible(fn ($get) => $get('simulation_type') === SimulationType::Matching->value)
                ->schema([
                    Repeater::make('matchingPairs')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['left_label'] ?? null)
                        ->columns(2)
                        ->components([
                            Textarea::make('left_label')->required(),
                            Textarea::make('right_label')->required(),
                            Textarea::make('left_description'),
                            Textarea::make('right_description'),
                            FileUpload::make('left_image_url')->image()->maxSize(5120)->directory('simulations/matching'),
                            FileUpload::make('right_image_url')->image()->maxSize(5120)->directory('simulations/matching'),
                        ]),
                ]),
            Section::make('Ordering Steps')
                ->columnSpanFull()
                ->visible(fn ($get) => $get('simulation_type') === SimulationType::Ordering->value)
                ->schema([
                    Repeater::make('orderingSteps')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->columns(1)
                        ->components([
                            Textarea::make('label')->required(),
                            FileUpload::make('image_url')->image()->maxSize(5120)->directory('simulations/ordering'),
                            TextInput::make('correct_position')->numeric()->required(),
                        ]),
                ]),
        ]);
    }
}
