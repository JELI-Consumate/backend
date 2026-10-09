<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComplaintAgencies\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ComplaintAgencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('short_name')
                ->label('Nama singkat')
                ->helperText('Judul kartu di beranda, mis. "BPSK" atau "SP4N-LAPOR!".')
                ->required()
                ->maxLength(50)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug())),
            TextInput::make('slug')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            TextInput::make('full_name')
                ->label('Nama lengkap')
                ->required()
                ->maxLength(255),
            Textarea::make('summary')
                ->label('Deskripsi singkat (kartu)')
                ->required()
                ->rows(2)
                ->maxLength(500),
            Textarea::make('description')
                ->label('Deskripsi lembaga (halaman detail)')
                ->required()
                ->rows(5),
            Repeater::make('when_to_contact')
                ->label('Kapan konsumen dapat menghubungi?')
                ->simple(
                    TextInput::make('item')->required()->maxLength(500)
                )
                ->reorderable()
                ->addActionLabel('Tambah poin')
                ->columnSpanFull(),
            Textarea::make('how_to_access')
                ->label('Bagaimana cara mengakses layanan?')
                ->rows(5),
            Textarea::make('notes')
                ->label('Catatan kewenangan / ketentuan')
                ->rows(3),
            FileUpload::make('logo_url')
                ->label('Logo')
                ->image()
                ->maxSize(2048)
                ->directory('complaint-agencies/logos'),
            Select::make('categories')
                ->label('Muncul di jenis permasalahan')
                ->relationship('categories', 'problem')
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
