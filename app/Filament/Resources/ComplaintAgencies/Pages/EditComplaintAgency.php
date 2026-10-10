<?php

namespace App\Filament\Resources\ComplaintAgencies\Pages;

use App\Filament\Resources\ComplaintAgencies\ComplaintAgencyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditComplaintAgency extends EditRecord
{
    protected static string $resource = ComplaintAgencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
