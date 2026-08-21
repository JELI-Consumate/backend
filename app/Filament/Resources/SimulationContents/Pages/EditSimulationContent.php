<?php

namespace App\Filament\Resources\SimulationContents\Pages;

use App\Filament\Resources\SimulationContents\SimulationContentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSimulationContent extends EditRecord
{
    protected static string $resource = SimulationContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
