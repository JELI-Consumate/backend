<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('journey.sector.name')->label('Sector')->sortable(),
                TextColumn::make('journey.title')->label('Journey')->sortable(),
                TextColumn::make('order')->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('estimated_minutes'),
                IconColumn::make('is_required')->boolean(),
                TextColumn::make('status')->badge(),
            ])
            // Sengaja tidak reorderable di sini: daftar ini bisa berisi
            // module dari banyak journey sekaligus, jadi drag-reorder lintas
            // journey akan merusak urutan "order" tiap journey (lihat urutan
            // yang benar & bisa di-drag di tab "Modules" pada halaman edit
            // Journey — ModulesRelationManager, yang sudah dibatasi ke satu
            // journey). Urutan tampil ikut order sector -> order journey ->
            // baru order module itu sendiri. Dibungkus lewat orderBy(Builder)
            // supaya kolom "order" (reserved word di SQL) di-quote dengan
            // benar oleh query builder, bukan ditulis manual.
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy(
                    DB::table('journeys')
                        ->join('sectors', 'sectors.id', '=', 'journeys.sector_id')
                        ->whereColumn('journeys.id', 'modules.journey_id')
                        ->select('sectors.order')
                        ->limit(1)
                )
                ->orderBy(
                    DB::table('journeys')
                        ->whereColumn('journeys.id', 'modules.journey_id')
                        ->select('journeys.order')
                        ->limit(1)
                )
                ->orderBy('modules.order'))
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
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
