<?php

namespace App\Filament\Resources\ReflectionContents\Pages;

use App\Filament\Concerns\AttachesContentToModulePage;
use App\Filament\Resources\ReflectionContents\ReflectionContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReflectionContent extends CreateRecord
{
    use AttachesContentToModulePage;

    protected static string $resource = ReflectionContentResource::class;
}
