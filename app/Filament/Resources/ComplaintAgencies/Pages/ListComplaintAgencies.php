<?php

namespace App\Filament\Resources\ComplaintAgencies\Pages;

use App\Filament\Resources\ComplaintAgencies\ComplaintAgencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComplaintAgencies extends ListRecords
{
    protected static string $resource = ComplaintAgencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
