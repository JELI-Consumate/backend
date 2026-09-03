<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReflectionContents\Schemas;

use App\Enums\ReflectionQuestionType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ReflectionContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')
                ->required()
                ->maxLength(200),
            TextInput::make('closing_title'),
            Textarea::make('opening_message')
                ->required(),
            Textarea::make('closing_message'),
            Section::make('Sections')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('sections')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->columns(2)
                        ->components([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(200)
                                ->columnSpanFull(),
                            Textarea::make('instruction')
                                ->columnSpanFull(),
                            Repeater::make('questions')
                                ->relationship()
                                ->orderColumn('order')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? null)
                                ->columnSpanFull()
                                ->components([
                                    Select::make('question_type')
                                        ->options(collect(ReflectionQuestionType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value]))
                                        ->required()
                                        ->live(),
                                    Textarea::make('question_text')
                                        ->required(),
                                    Repeater::make('checklistItems')
                                        ->relationship()
                                        ->label('Item checklist')
                                        ->orderColumn('order')
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                        ->visible(fn (Get $get): bool => $get('question_type') === ReflectionQuestionType::Checklist->value)
                                        ->components([
                                            TextInput::make('label')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }
}
