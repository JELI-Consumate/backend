<?php

namespace App\Filament\Resources\ArticleContents\Pages;

use App\Filament\Concerns\AttachesContentToModulePage;
use App\Filament\Resources\ArticleContents\ArticleContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleContent extends CreateRecord
{
    use AttachesContentToModulePage;

    protected static string $resource = ArticleContentResource::class;
}
