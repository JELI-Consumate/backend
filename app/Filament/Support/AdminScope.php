<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Pusat logika pembatasan akses per-sector untuk panel Filament.
 *
 * - super_admin: tidak dibatasi, lihat semua sector.
 * - admin: dibatasi ke satu sector (users.sector_id).
 *
 * Dipakai di getEloquentQuery() masing-masing Resource (bukan Policy) supaya
 * tidak bentrok dengan Policy yang sudah ada untuk API mobile (mis.
 * JourneyPolicy, yang punya arti "view" berbeda: unlock BR-01, bukan akses
 * admin panel).
 */
final class AdminScope
{
    public static function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public static function isSuperAdmin(): bool
    {
        return self::user()?->isSuperAdmin() ?? false;
    }

    /**
     * ID sector yang membatasi admin saat ini, atau null kalau tidak
     * dibatasi (super admin, atau belum ada user login).
     */
    public static function restrictedSectorId(): ?string
    {
        $user = self::user();

        if (! $user || $user->role !== UserRole::Admin) {
            return null;
        }

        return $user->sector_id;
    }

    /**
     * Batasi query struktural (Sector/Journey/dst.) lewat kolom sector_id
     * langsung di tabelnya sendiri.
     *
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public static function scopeSectorColumn(Builder $query, string $column = 'sector_id'): Builder
    {
        $sectorId = self::restrictedSectorId();

        return $sectorId === null ? $query : $query->where($column, $sectorId);
    }

    /**
     * Batasi query QuizContent (atau relasi yang whereHas ke QuizContent
     * lewat relasi bernama "quizContent") lewat dua jalur sector: langsung
     * via sector_id (kind pretest/posttest) atau tidak langsung via
     * journey_id (kind quiz).
     *
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public static function scopeQuizContentSector(Builder $query): Builder
    {
        $sectorId = self::restrictedSectorId();

        return $sectorId === null ? $query : $query->forSector($sectorId);
    }

    /**
     * Batasi query konten reusable (Article/Video/Simulation/Reflection
     * Content) yang baru terhubung ke sector lewat rantai
     * modulePage -> module -> journey -> sector, dan bisa belum ditempel ke
     * halaman modul manapun. Konten yang belum ditempel dianggap draft
     * bersama dan tetap terlihat oleh semua admin.
     *
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public static function scopeSectorContent(Builder $query): Builder
    {
        $sectorId = self::restrictedSectorId();

        if ($sectorId === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($sectorId): void {
            $q->whereDoesntHave('modulePage')
                ->orWhereHas('modulePage', function (Builder $modulePageQuery) use ($sectorId): void {
                    $modulePageQuery->whereHas('module', function (Builder $moduleQuery) use ($sectorId): void {
                        $moduleQuery->withoutGlobalScopes()
                            ->whereHas('journey', function (Builder $journeyQuery) use ($sectorId): void {
                                $journeyQuery->withoutGlobalScopes()->where('sector_id', $sectorId);
                            });
                    });
                });
        });
    }
}
