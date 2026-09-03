<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuizContents\Schemas;

use App\Enums\QuizKind;
use App\Enums\QuizSegmentType;
use App\Filament\Support\AdminScope;
use App\Models\Journey;
use App\Models\Sector;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('kind')
                ->options(collect(QuizKind::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value]))
                ->live()
                ->required(),
            Select::make('journey_id')
                ->label('Journey')
                ->options(fn () => Journey::withoutGlobalScopes()
                    ->when(
                        AdminScope::restrictedSectorId(),
                        fn ($query, $sectorId) => $query->where('sector_id', $sectorId),
                    )
                    ->pluck('title', 'id'))
                ->searchable()
                ->visible(fn ($get) => $get('kind') === QuizKind::Quiz->value)
                ->requiredIf('kind', QuizKind::Quiz->value),
            Select::make('sector_id')
                ->label('Sector')
                ->options(fn () => Sector::query()
                    ->when(
                        AdminScope::restrictedSectorId(),
                        fn ($query, $sectorId) => $query->whereKey($sectorId),
                    )
                    ->pluck('name', 'id'))
                ->default(fn () => AdminScope::restrictedSectorId())
                ->searchable()
                ->visible(fn ($get) => in_array($get('kind'), [QuizKind::Pretest->value, QuizKind::Posttest->value]))
                ->requiredIf('kind', [QuizKind::Pretest->value, QuizKind::Posttest->value]),
            TextInput::make('passing_score')
                ->numeric()
                ->required()
                ->default(70),
            Toggle::make('shuffle_questions')
                ->default(false),
            Section::make('Segments')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('segments')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->columns(2)
                        ->components([
                            Select::make('segment_type')
                                ->options(collect(QuizSegmentType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value]))
                                ->live()
                                ->required(),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(200),
                            Textarea::make('instruction')
                                ->columnSpanFull(),
                            Repeater::make('questions')
                                ->relationship()
                                ->orderColumn('order')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                ->visible(fn ($get) => $get('segment_type') === QuizSegmentType::MultipleChoice->value)
                                ->columnSpanFull()
                                ->components([
                                    Textarea::make('question')->required(),
                                    Textarea::make('explanation'),
                                    Repeater::make('choiceOptions')
                                        ->relationship()
                                        ->orderColumn('order')
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['option_text'] ?? null)
                                        ->components([
                                            TextInput::make('option_text')->required(),
                                            Toggle::make('is_correct'),
                                        ]),
                                ]),
                            Repeater::make('likertScaleOptions')
                                ->relationship()
                                ->orderColumn('order')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->visible(fn ($get) => $get('segment_type') === QuizSegmentType::Likert->value)
                                ->columnSpanFull()
                                ->components([
                                    TextInput::make('value')->numeric()->required(),
                                    TextInput::make('label')->required(),
                                ]),
                        ]),
                ]),
        ]);
    }
}
