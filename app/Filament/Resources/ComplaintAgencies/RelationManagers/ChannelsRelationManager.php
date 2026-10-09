<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComplaintAgencies\RelationManagers;

use App\Enums\ComplaintChannelType;
use App\Filament\Support\AdminScope;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Kanal kontak per lembaga (situs, WA, telepon, email, alamat). Admin hanya
 * mengisi `value` mentah; URL tel:/mailto:/wa.me dihitung di
 * ComplaintAgencyChannel::url(). Validasi `value` mengikuti `type` supaya
 * skema berbahaya (mis. javascript:) tidak bisa jadi tombol di app.
 */
class ChannelsRelationManager extends RelationManager
{
    protected static string $relationship = 'channels';

    protected static ?string $title = 'Kanal Kontak & Tombol';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return AdminScope::isSuperAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Jenis kanal')
                ->options(ComplaintChannelType::options())
                ->required()
                ->live(),
            TextInput::make('label')
                ->label('Label')
                ->helperText('Teks yang tampil, mis. "WhatsApp Halo BPOM".')
                ->required()
                ->maxLength(150),
            TextInput::make('value')
                ->label('Nilai')
                ->helperText('URL lengkap (https://…), nomor, email, atau teks alamat sesuai jenis kanal.')
                ->required()
                ->maxLength(2048)
                ->rules(fn (Get $get): array => match ($get('type')) {
                    ComplaintChannelType::Website->value, ComplaintChannelType::App->value => ['regex:#^https?://\S+$#i'],
                    ComplaintChannelType::Email->value => ['email'],
                    ComplaintChannelType::Whatsapp->value, ComplaintChannelType::Phone->value => ['regex:/^[0-9+()\s-]{3,30}$/'],
                    default => [],
                }),
            TextInput::make('button_label')
                ->label('Teks tombol (opsional)')
                ->helperText('Isi kalau kanal ini perlu jadi tombol aksi di app, mis. "Hubungi Halo BPOM". Kosongkan untuk info saja.')
                ->maxLength(100),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('order')->sortable(),
                TextColumn::make('type')->label('Jenis')->badge()
                    ->formatStateUsing(fn (ComplaintChannelType $state): string => $state->label()),
                TextColumn::make('label')->searchable(),
                TextColumn::make('value')->limit(40),
                TextColumn::make('button_label')->label('Tombol')->placeholder('—'),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
