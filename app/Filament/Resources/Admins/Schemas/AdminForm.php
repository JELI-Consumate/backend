<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Schemas;

use App\Enums\UserRole;
use App\Models\Sector;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->dehydrateStateUsing(fn (?string $state) => $state)
                ->helperText('Kosongkan kalau tidak ingin mengganti password.')
                ->maxLength(255),
            Select::make('role')
                ->label('Role')
                ->options([
                    UserRole::Admin->value => UserRole::Admin->label(),
                    UserRole::SuperAdmin->value => UserRole::SuperAdmin->label(),
                ])
                ->live()
                ->required(),
            Select::make('sector_id')
                ->label('Sector')
                ->options(fn () => Sector::query()->pluck('name', 'id'))
                ->searchable()
                ->visible(fn (Get $get): bool => $get('role') === UserRole::Admin->value)
                ->required(fn (Get $get): bool => $get('role') === UserRole::Admin->value)
                ->dehydrateStateUsing(fn ($state, Get $get) => $get('role') === UserRole::Admin->value ? $state : null)
                ->helperText('Admin hanya bisa mengelola satu sector ini.'),
        ]);
    }
}
