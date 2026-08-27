<?php

namespace App\Filament\Resources\Sectors;

use App\Filament\Resources\Sectors\Pages\CreateSector;
use App\Filament\Resources\Sectors\Pages\EditSector;
use App\Filament\Resources\Sectors\Pages\ListSectors;
use App\Filament\Resources\Sectors\RelationManagers\JourneysRelationManager;
use App\Filament\Resources\Sectors\Schemas\SectorForm;
use App\Filament\Resources\Sectors\Tables\SectorsTable;
use App\Filament\Support\AdminScope;
use App\Models\Sector;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SectorResource extends Resource
{
    protected static ?string $model = Sector::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Struktur Belajar';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SectorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JourneysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSectors::route('/'),
            'create' => CreateSector::route('/create'),
            'edit' => EditSector::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Admin sector hanya boleh melihat/mengelola sector-nya sendiri.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if ($sectorId = AdminScope::restrictedSectorId()) {
            $query->whereKey($sectorId);
        }

        return $query;
    }

    /**
     * Admin sector tidak boleh membuat sector baru maupun menghapus sector
     * (termasuk sector-nya sendiri) — hanya super admin.
     */
    public static function canCreate(): bool
    {
        return ! AdminScope::restrictedSectorId();
    }

    public static function canDelete(Model $record): bool
    {
        return ! AdminScope::restrictedSectorId();
    }

    public static function canDeleteAny(): bool
    {
        return ! AdminScope::restrictedSectorId();
    }

    public static function canForceDelete(Model $record): bool
    {
        return ! AdminScope::restrictedSectorId();
    }

    public static function canForceDeleteAny(): bool
    {
        return ! AdminScope::restrictedSectorId();
    }

    public static function canRestore(Model $record): bool
    {
        return ! AdminScope::restrictedSectorId();
    }

    public static function canRestoreAny(): bool
    {
        return ! AdminScope::restrictedSectorId();
    }
}
