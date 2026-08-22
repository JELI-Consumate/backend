<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReflectionContents\Schemas;

use App\Enums\ReflectionQuestionType;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;

class ReflectionContentPreview
{
    /**
     * @return array<Component>
     */
    public static function components(): array
    {
        return [
            TextEntry::make('title'),
            TextEntry::make('opening_message'),
            RepeatableEntry::make('sections')
                ->label('Sections')
                ->schema([
                    TextEntry::make('title'),
                    TextEntry::make('instruction'),
                    RepeatableEntry::make('questions')
                        ->label('Pertanyaan')
                        ->schema([
                            TextEntry::make('question_type')->badge(),
                            TextEntry::make('question_text'),
                            RepeatableEntry::make('checklistItems')
                                ->label('Item checklist')
                                ->visible(fn ($record): bool => $record?->question_type === ReflectionQuestionType::Checklist)
                                ->schema([
                                    TextEntry::make('label'),
                                ]),
                        ]),
                ]),
            TextEntry::make('closing_title'),
            TextEntry::make('closing_message'),
        ];
    }
}
