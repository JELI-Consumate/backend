<?php

namespace App\Filament\Resources\SimulationContents\Pages;

use App\Filament\Concerns\AttachesContentToModulePage;
use App\Filament\Resources\SimulationContents\SimulationContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSimulationContent extends CreateRecord
{
    use AttachesContentToModulePage;

    protected static string $resource = SimulationContentResource::class;
}
