<?php

namespace App\Filament\Resources\ReflectionContents;

use App\Filament\Resources\ReflectionContents\Pages\CreateReflectionContent;
use App\Filament\Resources\ReflectionContents\Pages\EditReflectionContent;
use App\Filament\Resources\ReflectionContents\Pages\ListReflectionContents;
use App\Filament\Resources\ReflectionContents\Schemas\ReflectionContentForm;
use App\Filament\Resources\ReflectionContents\Tables\ReflectionContentsTable;
use App\Filament\Support\AdminScope;
use App\Models\ReflectionContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ReflectionContentResource extends Resource
{
    protected static ?string $model = ReflectionContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Modul';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ReflectionContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReflectionContentsTable::configure($table);
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
            'index' => ListReflectionContents::route('/'),
            'create' => CreateReflectionContent::route('/create'),
            'edit' => EditReflectionContent::route('/{record}/edit'),
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
        return AdminScope::scopeSectorContent(parent::getEloquentQuery());
    }
}
