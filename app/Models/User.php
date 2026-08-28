<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'date_of_birth', 'password', 'google_id', 'avatar_url'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, MustVerifyEmail, Notifiable;

    /**
     * role & sector_id sengaja tidak masuk #[Fillable] di atas — tidak boleh
     * bisa di-set lewat endpoint register/update-profile, hanya lewat resource
     * "Kelola Admin" (super admin) atau seeder/tinker.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role->canAccessPanel();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Admin yang dibatasi hanya ke satu sector (lihat sector_id).
     */
    public function isSectorAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @return HasOne<EmailVerificationOtp, $this>
     */
    public function emailVerificationOtp(): HasOne
    {
        return $this->hasOne(EmailVerificationOtp::class);
    }

    /**
     * @return HasOne<PasswordResetOtp, $this>
     */
    public function passwordResetOtp(): HasOne
    {
        return $this->hasOne(PasswordResetOtp::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Sector yang boleh diakses ketika role = Admin. Null untuk super_admin
     * (akses semua sector) maupun pengguna biasa.
     *
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return HasMany<SectorProgress, $this>
     */
    public function sectorProgress(): HasMany
    {
        return $this->hasMany(SectorProgress::class);
    }

    /**
     * @return HasMany<JourneyProgress, $this>
     */
    public function journeyProgress(): HasMany
    {
        return $this->hasMany(JourneyProgress::class);
    }
}
