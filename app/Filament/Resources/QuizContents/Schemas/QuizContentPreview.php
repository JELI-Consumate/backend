<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuizContents\Schemas;

use App\Enums\QuizSegmentType;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;

class QuizContentPreview
{
    /**
     * @return array<Component>
     */
    public static function components(): array
    {
        return [
            TextEntry::make('kind')->badge(),
            TextEntry::make('passing_score')->suffix('%'),
            RepeatableEntry::make('segments')
                ->label('Segments')
                ->schema([
                    TextEntry::make('title'),
                    TextEntry::make('segment_type')->badge(),
                    TextEntry::make('instruction'),
                    RepeatableEntry::make('questions')
                        ->label('Soal')
                        ->visible(fn ($record) => $record->segment_type === QuizSegmentType::MultipleChoice)
                        ->schema([
                            TextEntry::make('question'),
                            RepeatableEntry::make('choiceOptions')
                                ->label('Pilihan')
                                ->schema([
                                    TextEntry::make('option_text'),
                                    TextEntry::make('is_correct')
                                        ->badge()
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Benar' : 'Salah')
                                        ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                ]),
                        ]),
                    RepeatableEntry::make('likertScaleOptions')
                        ->label('Skala Likert')
                        ->visible(fn ($record) => $record->segment_type === QuizSegmentType::Likert)
                        ->schema([
                            TextEntry::make('value'),
                            TextEntry::make('label'),
                        ]),
                ]),
        ];
    }
}
