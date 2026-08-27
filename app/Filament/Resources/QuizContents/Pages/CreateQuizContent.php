<?php

namespace App\Filament\Resources\QuizContents\Pages;

use App\Filament\Concerns\AttachesContentToModulePage;
use App\Filament\Resources\QuizContents\QuizContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuizContent extends CreateRecord
{
    use AttachesContentToModulePage;

    protected static string $resource = QuizContentResource::class;
}
