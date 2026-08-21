<?php

namespace App\Filament\Resources\ArticleContents\Pages;

use App\Filament\Resources\ArticleContents\ArticleContentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditArticleContent extends EditRecord
{
    protected static string $resource = ArticleContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
