<?php

namespace App\Filament\Resources\VideoContents;

use App\Filament\Resources\VideoContents\Pages\CreateVideoContent;
use App\Filament\Resources\VideoContents\Pages\EditVideoContent;
use App\Filament\Resources\VideoContents\Pages\ListVideoContents;
use App\Filament\Resources\VideoContents\Schemas\VideoContentForm;
use App\Filament\Resources\VideoContents\Tables\VideoContentsTable;
use App\Models\VideoContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VideoContentResource extends Resource
{
    protected static ?string $model = VideoContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Modul';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return VideoContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VideoContentsTable::configure($table);
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
            'index' => ListVideoContents::route('/'),
            'create' => CreateVideoContent::route('/create'),
            'edit' => EditVideoContent::route('/{record}/edit'),
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
