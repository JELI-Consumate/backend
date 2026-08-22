<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'date_of_birth', 'password', 'google_id', 'avatar_url'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * TODO: belum ada sistem role/admin flag di skema — seluruh user terautentikasi
     * saat ini diizinkan masuk admin panel. Perlu keputusan Faqih sebelum rilis
     * (mis. kolom is_admin atau tabel role terpisah) — lihat 06-nonfunctional-ops.md.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
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
