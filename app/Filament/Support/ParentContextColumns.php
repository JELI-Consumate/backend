<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Tables\Columns\TextColumn;

/**
 * Kolom konteks induk (Sector/Journey/Module) untuk resource konten reusable
 * (Article/Video/Simulation/ReflectionContent) yang cuma terhubung ke
 * hierarki lewat ModulePage — supaya kelihatan konten itu dipakai di
 * module/journey/sector mana, tanpa harus buka satu-satu.
 */
final class ParentContextColumns
{
    /**
     * @return array<TextColumn>
     */
    public static function forModulePage(): array
    {
        return [
            TextColumn::make('modulePage.module.journey.sector.name')
                ->label('Sector')
                ->placeholder('Belum ditempel')
                ->toggleable(),
            TextColumn::make('modulePage.module.journey.title')
                ->label('Journey')
                ->placeholder('Belum ditempel')
                ->toggleable(),
            TextColumn::make('modulePage.module.title')
                ->label('Module')
                ->placeholder('Belum ditempel')
                ->toggleable(),
        ];
    }
}
