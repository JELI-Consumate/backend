<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComplaintCategories\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ComplaintCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('problem')
                ->label('Permasalahan konsumen')
                ->helperText('Mis. "Kendala pada dompet digital atau pembayaran melalui QRIS".')
                ->required()
                ->rows(2)
                ->maxLength(500),
            Select::make('agencies')
                ->label('Lembaga yang dapat dipertimbangkan')
                ->relationship('agencies', 'short_name')
                ->multiple()
                ->preload(),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
            Checkbox::make('is_active')
                ->default(true),
        ]);
    }
}
