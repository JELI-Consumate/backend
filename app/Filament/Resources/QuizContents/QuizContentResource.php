<?php

namespace App\Filament\Resources\QuizContents;

use App\Filament\Resources\QuizContents\Pages\CreateQuizContent;
use App\Filament\Resources\QuizContents\Pages\EditQuizContent;
use App\Filament\Resources\QuizContents\Pages\ListQuizContents;
use App\Filament\Resources\QuizContents\Schemas\QuizContentForm;
use App\Filament\Resources\QuizContents\Tables\QuizContentsTable;
use App\Filament\Support\AdminScope;
use App\Models\QuizContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class QuizContentResource extends Resource
{
    protected static ?string $model = QuizContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Modul';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return QuizContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizContentsTable::configure($table);
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
            'index' => ListQuizContents::route('/'),
            'create' => CreateQuizContent::route('/create'),
            'edit' => EditQuizContent::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminScope::scopeQuizContentSector(parent::getEloquentQuery());
    }
}
