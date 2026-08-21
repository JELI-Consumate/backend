<?php

namespace App\Filament\Resources\ArticleContents\Pages;

use App\Filament\Resources\ArticleContents\ArticleContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticleContents extends ListRecords
{
    protected static string $resource = ArticleContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
