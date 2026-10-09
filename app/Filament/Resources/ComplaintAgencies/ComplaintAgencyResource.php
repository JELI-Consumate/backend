<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComplaintAgencies;

use App\Filament\Resources\ComplaintAgencies\Pages\CreateComplaintAgency;
use App\Filament\Resources\ComplaintAgencies\Pages\EditComplaintAgency;
use App\Filament\Resources\ComplaintAgencies\Pages\ListComplaintAgencies;
use App\Filament\Resources\ComplaintAgencies\RelationManagers\ChannelsRelationManager;
use App\Filament\Resources\ComplaintAgencies\Schemas\ComplaintAgencyForm;
use App\Filament\Resources\ComplaintAgencies\Tables\ComplaintAgenciesTable;
use App\Filament\Support\AdminScope;
use App\Models\ComplaintAgency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Lembaga pengaduan bersifat global (bukan per-sector): semua admin boleh
 * melihat, hanya super admin yang boleh mengubah.
 */
class ComplaintAgencyResource extends Resource
{
    protected static ?string $model = ComplaintAgency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Pusat Pengaduan';

    protected static ?string $modelLabel = 'Lembaga Pengaduan';

    protected static ?string $pluralModelLabel = 'Lembaga Pengaduan';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ComplaintAgencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComplaintAgenciesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ChannelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplaintAgencies::route('/'),
            'create' => CreateComplaintAgency::route('/create'),
            'edit' => EditComplaintAgency::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canForceDelete(Model $record): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canForceDeleteAny(): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canRestore(Model $record): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canRestoreAny(): bool
    {
        return AdminScope::isSuperAdmin();
    }
}
