<?php

namespace App\Filament\Resources\VideoContents\Pages;

use App\Filament\Resources\VideoContents\VideoContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVideoContents extends ListRecords
{
    protected static string $resource = VideoContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
