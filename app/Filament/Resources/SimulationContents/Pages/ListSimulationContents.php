<?php

namespace App\Filament\Resources\SimulationContents\Pages;

use App\Filament\Resources\SimulationContents\SimulationContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSimulationContents extends ListRecords
{
    protected static string $resource = SimulationContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
