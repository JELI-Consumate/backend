<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * role & sector_id sengaja tidak masuk #[Fillable] User (lihat User::class),
     * jadi di sini di-set eksplisit lewat forceFill(). Field password sudah
     * di-dehydrated(false) di AdminForm kalau dikosongkan, jadi tidak perlu
     * dicek ulang di sini.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->forceFill($data);
        $record->save();

        return $record;
    }
}
