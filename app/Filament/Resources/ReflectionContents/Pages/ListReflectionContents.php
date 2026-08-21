<?php

namespace App\Filament\Resources\ReflectionContents\Pages;

use App\Filament\Resources\ReflectionContents\ReflectionContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReflectionContents extends ListRecords
{
    protected static string $resource = ReflectionContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
