<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReflectionContents\Schemas;

use App\Enums\ReflectionQuestionType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReflectionContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(200),
            Textarea::make('opening_message')
                ->required(),
            TextInput::make('closing_title'),
            Textarea::make('closing_message'),
            Repeater::make('sections')
                ->relationship()
                ->orderColumn('order')
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->components([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(200),
                    Textarea::make('instruction'),
                    Repeater::make('questions')
                        ->relationship()
                        ->orderColumn('order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? null)
                        ->components([
                            Select::make('question_type')
                                ->options(collect(ReflectionQuestionType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value]))
                                ->required(),
                            Textarea::make('question_text')
                                ->required(),
                        ]),
                ]),
        ]);
    }
}
