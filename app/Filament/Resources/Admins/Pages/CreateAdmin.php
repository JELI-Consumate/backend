<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    /**
     * role & sector_id sengaja tidak masuk #[Fillable] User (lihat User::class)
     * supaya tidak bisa di-mass-assign lewat endpoint mobile, jadi di sini
     * di-set eksplisit lewat forceFill(). Akun yang dibuat lewat panel juga
     * langsung terverifikasi — tidak perlu lewat alur OTP registrasi mobile.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['email_verified_at'] = now();

        $record = new (static::getModel());
        $record->forceFill($data);
        $record->save();

        return $record;
    }
}
