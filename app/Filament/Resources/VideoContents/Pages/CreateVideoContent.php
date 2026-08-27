<?php

namespace App\Filament\Resources\VideoContents\Pages;

use App\Filament\Concerns\AttachesContentToModulePage;
use App\Filament\Resources\VideoContents\VideoContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoContent extends CreateRecord
{
    use AttachesContentToModulePage;

    protected static string $resource = VideoContentResource::class;
}
