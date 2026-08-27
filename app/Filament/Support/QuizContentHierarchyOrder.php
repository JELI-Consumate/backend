<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\QuizKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Urutan tampil default untuk QuizContent. Beda dari ContentHierarchyOrder
 * karena QuizContent punya sector_id/journey_id langsung di tabelnya
 * sendiri (tidak perlu lewat ModulePage untuk tahu induknya), dan ada dua
 * level: kind "quiz" nempel ke satu Journey, kind "pretest"/"posttest"
 * nempel langsung ke Sector.
 *
 * Dalam satu sector: pretest -> journey 1, 2, ... (urut sesuai order
 * journey-nya) -> posttest.
 */
final class QuizContentHierarchyOrder
{
    public static function apply(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        $sectorOrderViaJourney = DB::table('journeys')
            ->join('sectors', 'sectors.id', '=', 'journeys.sector_id')
            ->whereColumn('journeys.id', "{$table}.journey_id")
            ->select('sectors.order')
            ->limit(1);

        $sectorOrderDirect = DB::table('sectors')
            ->whereColumn('sectors.id', "{$table}.sector_id")
            ->select('sectors.order')
            ->limit(1);

        $journeyOrder = DB::table('journeys')
            ->whereColumn('journeys.id', "{$table}.journey_id")
            ->select('journeys.order')
            ->limit(1);

        return $query
            ->orderByRaw(
                'coalesce(('.$sectorOrderDirect->toSql().'), ('.$sectorOrderViaJourney->toSql().')) asc',
                [...$sectorOrderDirect->getBindings(), ...$sectorOrderViaJourney->getBindings()],
            )
            ->orderByRaw(
                "case {$table}.kind when ? then 0 when ? then 2 else 1 end asc",
                [QuizKind::Pretest->value, QuizKind::Posttest->value],
            )
            ->orderBy($journeyOrder);
    }
}
