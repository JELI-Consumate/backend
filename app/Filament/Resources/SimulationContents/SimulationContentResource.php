<?php

namespace App\Filament\Resources\SimulationContents;

use App\Filament\Resources\SimulationContents\Pages\CreateSimulationContent;
use App\Filament\Resources\SimulationContents\Pages\EditSimulationContent;
use App\Filament\Resources\SimulationContents\Pages\ListSimulationContents;
use App\Filament\Resources\SimulationContents\Schemas\SimulationContentForm;
use App\Filament\Resources\SimulationContents\Tables\SimulationContentsTable;
use App\Models\SimulationContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SimulationContentResource extends Resource
{
    protected static ?string $model = SimulationContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Modul';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return SimulationContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SimulationContentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSimulationContents::route('/'),
            'create' => CreateSimulationContent::route('/create'),
            'edit' => EditSimulationContent::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
