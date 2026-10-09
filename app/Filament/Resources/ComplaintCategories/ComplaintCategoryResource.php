<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComplaintCategories;

use App\Filament\Resources\ComplaintCategories\Pages\CreateComplaintCategory;
use App\Filament\Resources\ComplaintCategories\Pages\EditComplaintCategory;
use App\Filament\Resources\ComplaintCategories\Pages\ListComplaintCategories;
use App\Filament\Resources\ComplaintCategories\Schemas\ComplaintCategoryForm;
use App\Filament\Resources\ComplaintCategories\Tables\ComplaintCategoriesTable;
use App\Filament\Support\AdminScope;
use App\Models\ComplaintCategory;
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
 * "Bingung harus mengadu ke mana?" -- pemetaan jenis permasalahan ke lembaga.
 * Global, semua admin boleh lihat, hanya super admin yang boleh ubah.
 */
class ComplaintCategoryResource extends Resource
{
    protected static ?string $model = ComplaintCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Pusat Pengaduan';

    protected static ?string $modelLabel = 'Jenis Permasalahan';

    protected static ?string $pluralModelLabel = 'Jenis Permasalahan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ComplaintCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComplaintCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplaintCategories::route('/'),
            'create' => CreateComplaintCategory::route('/create'),
            'edit' => EditComplaintCategory::route('/{record}/edit'),
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
