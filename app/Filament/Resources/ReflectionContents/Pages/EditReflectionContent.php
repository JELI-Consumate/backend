<?php

namespace App\Filament\Resources\ReflectionContents\Pages;

use App\Filament\Resources\ReflectionContents\ReflectionContentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditReflectionContent extends EditRecord
{
    protected static string $resource = ReflectionContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
