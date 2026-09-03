<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\JourneyProgressRelationManager;
use App\Filament\Resources\Users\RelationManagers\ModuleProgressRelationManager;
use App\Filament\Resources\Users\RelationManagers\QuizAttemptsRelationManager;
use App\Filament\Resources\Users\RelationManagers\SectorProgressRelationManager;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Support\AdminScope;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only: peneliti hanya perlu melihat data user + progres belajarnya,
 * tidak pernah membuat/mengedit/menghapus user lewat panel admin.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna & Analitik';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SectorProgressRelationManager::class,
            JourneyProgressRelationManager::class,
            ModuleProgressRelationManager::class,
            QuizAttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
        ];
    }

    /**
     * Hanya pengguna aplikasi biasa yang tampil di sini (akun admin/super
     * admin dikelola lewat resource "Kelola Admin"). Admin sector hanya
     * melihat pengguna yang punya progres di sector-nya.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', UserRole::User);

        if ($sectorId = AdminScope::restrictedSectorId()) {
            $query->whereHas('sectorProgress', fn (Builder $q) => $q->where('sector_id', $sectorId));
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
