<?php

declare(strict_types=1);

namespace App\Filament\Resources\Journeys\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

/**
 * Badge yang otomatis diberikan ke user begitu journey ini selesai (BR-07,
 * lihat JourneyCompleted -> AwardJourneyBadge -> BadgeService). Relasinya
 * HasOne (unique index di `badges.journey_id`) -- tabel di bawah karena itu
 * cuma pernah berisi 0 atau 1 baris, dan tombol "Buat" disembunyikan begitu
 * badge-nya sudah ada supaya admin tidak coba bikin badge kedua yang bakal
 * ditolak constraint unique di DB.
 */
class BadgeRelationManager extends RelationManager
{
    protected static string $relationship = 'badge';

    protected static ?string $title = 'Badge';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Badge')
                ->helperText('Mis. "Consumer Rights Explorer (Penjelajah Hak Konsumen)".')
                ->required()
                ->maxLength(150),
            Textarea::make('description')
                ->label('Deskripsi Badge')
                ->helperText('Tampil di bagian "Deskripsi Badge" pada layar pencapaian.')
                ->required()
                ->rows(3),
            Textarea::make('congratulation_message')
                ->label('Pesan Ucapan Selamat')
                ->helperText('Tampil di bawah judul "Pencapaian Baru: {nama badge}" begitu journey selesai.')
                ->required()
                ->rows(4),
            Textarea::make('motivational_message')
                ->label('Pesan Motivasi')
                ->helperText('Tampil di bagian "Pesan Motivasi", mengajak user lanjut ke journey berikutnya.')
                ->required()
                ->rows(4),
            FileUpload::make('icon_url')
                ->label('Gambar Badge')
                ->image()
                ->maxSize(5120)
                ->directory('badges')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('icon_url')->label('Ikon'),
                TextColumn::make('name')->label('Nama Badge')->searchable(),
                TextColumn::make('description')->label('Deskripsi')->limit(60),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->badge === null),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([]);
    }
}
