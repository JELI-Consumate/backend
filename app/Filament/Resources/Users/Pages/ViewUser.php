<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Flex::make([
                        ImageEntry::make('avatar_url')
                            ->hiddenLabel()
                            ->circular()
                            ->imageSize(80)
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name))
                            ->grow(false),
                        Group::make([
                            Text::make(fn ($record) => $record->name)->size(TextSize::Large)->weight('bold'),
                            Text::make(fn ($record) => $record->email)->color('gray'),
                        ]),
                    ])->verticallyAlignCenter(),
                ]),
            Section::make('Detail Pengguna')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    TextEntry::make('phone')->label('No. HP')->placeholder('Belum diisi'),
                    TextEntry::make('date_of_birth')->label('Tanggal Lahir')->date('d M Y')->placeholder('Belum diisi'),
                    TextEntry::make('email_verified_at')
                        ->label('Status Verifikasi')
                        ->badge()
                        ->state(fn ($record) => $record->email_verified_at ? 'Terverifikasi' : 'Belum Terverifikasi')
                        ->color(fn ($record) => $record->email_verified_at ? 'success' : 'danger'),
                    TextEntry::make('last_active_at')
                        ->label('Terakhir Aktif')
                        ->since()
                        ->placeholder('Belum pernah aktif'),
                    TextEntry::make('created_at')->label('Terdaftar')->dateTime('d M Y, H:i'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
