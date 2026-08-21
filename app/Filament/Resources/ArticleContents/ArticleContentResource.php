<?php

namespace App\Filament\Resources\ArticleContents;

use App\Filament\Resources\ArticleContents\Pages\CreateArticleContent;
use App\Filament\Resources\ArticleContents\Pages\EditArticleContent;
use App\Filament\Resources\ArticleContents\Pages\ListArticleContents;
use App\Filament\Resources\ArticleContents\Schemas\ArticleContentForm;
use App\Filament\Resources\ArticleContents\Tables\ArticleContentsTable;
use App\Models\ArticleContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleContentResource extends Resource
{
    protected static ?string $model = ArticleContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ArticleContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleContentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleContents::route('/'),
            'create' => CreateArticleContent::route('/create'),
            'edit' => EditArticleContent::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
