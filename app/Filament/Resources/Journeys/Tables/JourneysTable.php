<?php

declare(strict_types=1);

namespace App\Filament\Resources\Journeys\Tables;

use App\Enums\PublishStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class JourneysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Foto')
                    ->disk(config('filament.default_filesystem_disk'))
                    ->height(40),
                TextColumn::make('sector.name')->label('Sector')->sortable(),
                TextColumn::make('order')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('estimated_minutes')->label('Durasi (menit)'),
            ])
            // Sengaja tidak reorderable di sini: daftar ini bisa berisi
            // journey dari banyak sector sekaligus, jadi drag-reorder lintas
            // sector akan merusak urutan "order" tiap sector (lihat urutan
            // yang benar & bisa di-drag di tab "Journeys" pada halaman edit
            // Sector — JourneysRelationManager, yang sudah dibatasi ke satu
            // sector). Urutan tampil ikut order sector induknya dulu, baru
            // order journey itu sendiri. Dibungkus lewat orderBy(Builder)
            // supaya kolom "order" (reserved word di SQL) di-quote dengan
            // benar oleh query builder, bukan ditulis manual.
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy(
                    DB::table('sectors')
                        ->whereColumn('sectors.id', 'journeys.sector_id')
                        ->select('sectors.order')
                        ->limit(1)
                )
                ->orderBy('journeys.order'))
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->visible(fn ($record) => $record->status !== PublishStatus::Published)
                    ->action(fn ($record) => $record->update(['status' => PublishStatus::Published, 'published_at' => now()])),
                Action::make('unpublish')
                    ->visible(fn ($record) => $record->status === PublishStatus::Published)
                    ->action(fn ($record) => $record->update(['status' => PublishStatus::Draft])),
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
