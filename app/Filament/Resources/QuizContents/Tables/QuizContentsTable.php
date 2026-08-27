<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuizContents\Tables;

use App\Filament\Resources\QuizContents\Schemas\QuizContentPreview;
use App\Filament\Support\QuizContentHierarchyOrder;
use App\Models\QuizContent;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['sector', 'journey.sector', 'modulePage.module', 'segments.questions.choiceOptions', 'segments.likertScaleOptions']))
            ->columns([
                TextColumn::make('kind')->badge(),
                // kind "quiz" tidak punya sector_id langsung (cuma journey_id),
                // jadi kolom sector.name saja akan kosong untuk baris itu.
                // Tampilkan sector efektifnya: langsung kalau ada, kalau
                // tidak turunkan dari sector milik journey-nya.
                TextColumn::make('sector')
                    ->label('Sector')
                    ->state(fn (QuizContent $record): ?string => $record->sector?->name ?? $record->journey?->sector?->name),
                TextColumn::make('journey.title')->label('Journey')->placeholder('—'),
                TextColumn::make('modulePage.module.title')->label('Module')->placeholder('Belum ditempel'),
                TextColumn::make('passing_score'),
                TextColumn::make('segments_count')->counts('segments')->label('Segments'),
            ])
            // Urutan default: sector (efektif) -> pretest -> journey demi
            // journey sesuai order-nya -> posttest. Lihat QuizContentHierarchyOrder.
            ->defaultSort(fn (Builder $query): Builder => QuizContentHierarchyOrder::apply($query))
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading('Preview Kuis')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->schema(QuizContentPreview::components()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
