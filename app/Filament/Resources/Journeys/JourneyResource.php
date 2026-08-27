<?php

namespace App\Filament\Resources\Journeys;

use App\Filament\Resources\Journeys\Pages\CreateJourney;
use App\Filament\Resources\Journeys\Pages\EditJourney;
use App\Filament\Resources\Journeys\Pages\ListJourneys;
use App\Filament\Resources\Journeys\RelationManagers\ModulesRelationManager;
use App\Filament\Resources\Journeys\Schemas\JourneyForm;
use App\Filament\Resources\Journeys\Tables\JourneysTable;
use App\Filament\Support\AdminScope;
use App\Models\Journey;
use App\Models\Scopes\Published;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class JourneyResource extends Resource
{
    protected static ?string $model = Journey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Struktur Belajar';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return JourneyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JourneysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ModulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJourneys::route('/'),
            'create' => CreateJourney::route('/create'),
            'edit' => EditJourney::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminScope::scopeSectorColumn(
            parent::getEloquentQuery()->withoutGlobalScope(Published::class)
        );
    }
}
