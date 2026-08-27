<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins;

use App\Enums\UserRole;
use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Admins\Schemas\AdminForm;
use App\Filament\Resources\Admins\Tables\AdminsTable;
use App\Filament\Support\AdminScope;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Kelola akun admin panel (role admin/super_admin) — hanya bisa diakses oleh
 * super admin. Berbeda dari UserResource yang read-only untuk pengguna
 * aplikasi biasa.
 */
class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?string $navigationLabel = 'Kelola Admin';

    protected static ?string $modelLabel = 'admin';

    protected static ?string $pluralModelLabel = 'admin';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return AdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }

    /**
     * Hanya akun admin/super_admin yang tampil di sini. Kalau yang login
     * bukan super admin, kosongkan hasilnya sama sekali — jangan andalkan
     * shouldRegisterNavigation/canViewAny saja karena ListRecords tidak
     * memanggil otorisasi itu (lihat vendor Filament ListRecords::authorizeAccess).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', '!=', UserRole::User);

        if (! AdminScope::isSuperAdmin()) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public static function canViewAny(): bool
    {
        return AdminScope::isSuperAdmin();
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
}
