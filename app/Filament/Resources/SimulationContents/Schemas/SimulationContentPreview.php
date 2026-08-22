<?php

declare(strict_types=1);

namespace App\Filament\Resources\SimulationContents\Schemas;

use App\Enums\SimulationType;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;

class SimulationContentPreview
{
    /**
     * @return array<Component>
     */
    public static function components(): array
    {
        return [
            TextEntry::make('title'),
            TextEntry::make('simulation_type')->badge(),
            TextEntry::make('scenario'),
            RepeatableEntry::make('matchingPairs')
                ->label('Pasangan Matching')
                ->visible(fn ($record) => $record->simulation_type === SimulationType::Matching)
                ->schema([
                    TextEntry::make('left_label')->label('Kiri'),
                    ImageEntry::make('left_image_url')->label('Gambar Kiri'),
                    TextEntry::make('right_label')->label('Kanan'),
                    ImageEntry::make('right_image_url')->label('Gambar Kanan'),
                ]),
            RepeatableEntry::make('orderingSteps')
                ->label('Urutan Langkah (Benar → Salah Ditampilkan Sesuai correct_position)')
                ->visible(fn ($record) => $record->simulation_type === SimulationType::Ordering)
                ->schema([
                    TextEntry::make('label'),
                    ImageEntry::make('image_url'),
                    TextEntry::make('correct_position')->label('Urutan Benar'),
                ]),
        ];
    }
}
