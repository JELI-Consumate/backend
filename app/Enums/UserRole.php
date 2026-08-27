<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Role akses panel admin Filament. `User` = pengguna aplikasi biasa (tidak
 * bisa login ke panel). `Admin` dibatasi hanya ke satu sector (users.sector_id).
 * `SuperAdmin` bisa akses seluruh sector.
 */
enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Pengguna',
            self::Admin => 'Admin Sektor',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function canAccessPanel(): bool
    {
        return $this !== self::User;
    }
}
