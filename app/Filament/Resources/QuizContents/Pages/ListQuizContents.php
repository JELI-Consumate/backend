<?php

namespace App\Filament\Resources\QuizContents\Pages;

use App\Filament\Resources\QuizContents\QuizContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizContents extends ListRecords
{
    protected static string $resource = QuizContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
