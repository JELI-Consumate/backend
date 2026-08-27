<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Urutan tampil default untuk konten reusable (Article/Video/Simulation/
 * ReflectionContent): ikuti posisi induknya di hierarki
 * Sector -> Journey -> Module -> urutan halaman, bukan title/id/created_at.
 * Konten yang belum ditempel ke module manapun ditaruh paling akhir.
 *
 * Sengaja pakai subquery lewat orderBy(Builder) — bukan JOIN di query utama
 * dan bukan string SQL mentah:
 * - JOIN ke modules/journeys/sectors akan bikin kolom senama (title, id,
 *   order, deleted_at) jadi ambigu untuk fitur searchable()/sortable()
 *   bawaan Filament di kolom milik tabel konten itu sendiri.
 * - "order" adalah reserved word di SQL (MySQL & SQLite) — kalau ditulis
 *   manual sebagai string mentah gampang salah quote. orderBy(Builder)
 *   biar query builder yang meng-quote kolomnya, otomatis benar di kedua
 *   database.
 * - Subquery (dibanding JOIN) juga tidak menggandakan baris kalau satu
 *   konten sampai ditempel ke lebih dari satu ModulePage (skema saat ini
 *   tidak melarangnya).
 */
final class ContentHierarchyOrder
{
    public static function apply(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();
        $morphType = $query->getModel()->getMorphClass();

        $modulePages = fn () => DB::table('module_pages')
            ->whereColumn('module_pages.contentable_id', "{$table}.id")
            ->where('module_pages.contentable_type', $morphType);

        return $query
            // count = 0 (belum ditempel) ditaruh paling akhir.
            ->orderBy($modulePages()->selectRaw('count(*)'), 'desc')
            ->orderBy(
                $modulePages()
                    ->join('modules', 'modules.id', '=', 'module_pages.module_id')
                    ->join('journeys', 'journeys.id', '=', 'modules.journey_id')
                    ->join('sectors', 'sectors.id', '=', 'journeys.sector_id')
                    ->select('sectors.order')
                    ->limit(1)
            )
            ->orderBy(
                $modulePages()
                    ->join('modules', 'modules.id', '=', 'module_pages.module_id')
                    ->join('journeys', 'journeys.id', '=', 'modules.journey_id')
                    ->select('journeys.order')
                    ->limit(1)
            )
            ->orderBy(
                $modulePages()
                    ->join('modules', 'modules.id', '=', 'module_pages.module_id')
                    ->select('modules.order')
                    ->limit(1)
            )
            ->orderBy(
                $modulePages()->select('module_pages.order')->limit(1)
            );
    }
}
