<?php

namespace App\Filament\Resources\QuizContents\Pages;

use App\Filament\Resources\QuizContents\QuizContentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditQuizContent extends EditRecord
{
    protected static string $resource = QuizContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
