<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\Schemas;

use App\Enums\ModuleType;
use App\Enums\PublishStatus;
use App\Filament\Support\AdminScope;
use App\Models\Journey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('journey_id')
                ->label('Journey')
                ->options(fn () => Journey::withoutGlobalScopes()
                    ->when(
                        AdminScope::restrictedSectorId(),
                        fn ($query, $sectorId) => $query->where('sector_id', $sectorId),
                    )
                    ->pluck('title', 'id'))
                ->searchable()
                ->required(),
            Select::make('type')
                ->options(collect(ModuleType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                ->required(),
            TextInput::make('title')
                ->required()
                ->maxLength(200),
            Textarea::make('description'),
            TextInput::make('estimated_minutes')
                ->numeric()
                ->required()
                ->default(5),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
            Toggle::make('is_required')
                ->default(true),
            Select::make('status')
                ->options(collect(PublishStatus::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)]))
                ->required()
                ->default(PublishStatus::Draft->value),
        ]);
    }
}
