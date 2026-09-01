<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use App\Services\Gamification\EmpowermentIndexService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query agregat untuk halaman admin "Learning Analytics"
 * (06-nonfunctional-ops.md §10): user aktif, skor & kelulusan kuis, tingkat
 * penyelesaian per journey, distribusi indeks keberdayaan.
 *
 * Dipisah dari Page/Widget Filament supaya bisa dipakai bareng oleh
 * keduanya dan gampang dites tanpa Livewire. `$sectorId` null berarti tidak
 * dibatasi (super admin); isi berarti dibatasi ke satu sector (admin) —
 * lihat App\Filament\Support\AdminScope::restrictedSectorId().
 */
final readonly class LearningAnalyticsService
{
    public function __construct(private EmpowermentIndexService $empowermentIndex) {}

    public function activeUsersCount(?string $sectorId, int $days = 30): int
    {
        return ModuleProgress::query()
            ->where('updated_at', '>=', now()->subDays($days))
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereHas(
                'modulePage',
                fn (Builder $mq) => $mq->whereHas(
                    'module',
                    fn (Builder $modq) => $modq->withoutGlobalScopes()->whereHas(
                        'journey',
                        fn (Builder $jq) => $jq->withoutGlobalScopes()->where('sector_id', $sectorId)
                    )
                )
            ))
            ->distinct('user_id')
            ->count('user_id');
    }

    public function averageQuizScore(?string $sectorId): ?float
    {
        $value = $this->completedQuizAttempts($sectorId)
            ->selectRaw('avg(choice_score * 100.0 / choice_max_score) as avg_pct')
            ->value('avg_pct');

        return $value === null ? null : round((float) $value, 1);
    }

    public function quizPassRate(?string $sectorId): ?float
    {
        $attempts = $this->completedQuizAttempts($sectorId);

        $total = (clone $attempts)->count();

        if ($total === 0) {
            return null;
        }

        $passed = (clone $attempts)->where('passed', true)->count();

        return round($passed * 100 / $total, 1);
    }

    /**
     * @return array<int, array{sector: string, title: string, total: int, completed: int, percent: int}>
     */
    public function journeyCompletion(?string $sectorId): array
    {
        $journeys = Journey::withoutGlobalScopes()
            ->with('sector')
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->where('sector_id', $sectorId))
            ->get()
            ->sortBy([
                ['sector.order', 'asc'],
                ['order', 'asc'],
            ])
            ->values();

        return $journeys->map(function (Journey $journey): array {
            $total = JourneyProgress::query()->where('journey_id', $journey->id)->count();
            $completed = JourneyProgress::query()
                ->where('journey_id', $journey->id)
                ->where('status', ProgressStatus::Completed)
                ->count();

            return [
                'sector' => $journey->sector?->name ?? '—',
                'title' => $journey->title,
                'total' => $total,
                'completed' => $completed,
                'percent' => $total > 0 ? (int) round($completed * 100 / $total) : 0,
            ];
        })->all();
    }

    /**
     * @return array{'0-25': int, '25-50': int, '50-75': int, '75-100': int}
     */
    public function empowermentIndexDistribution(?string $sectorId): array
    {
        $buckets = ['0-25' => 0, '25-50' => 0, '50-75' => 0, '75-100' => 0];

        $userIds = SectorProgress::query()
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->where('sector_id', $sectorId))
            ->distinct()
            ->pluck('user_id');

        $sectors = Sector::query()->active()
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereKey($sectorId))
            ->get();

        if ($userIds->isEmpty() || $sectors->isEmpty()) {
            return $buckets;
        }

        $users = User::query()->whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            foreach ($sectors as $sector) {
                $index = $this->empowermentIndex->calculate($user, $sector);

                $bucket = match (true) {
                    $index >= 75 => '75-100',
                    $index >= 50 => '50-75',
                    $index >= 25 => '25-50',
                    default => '0-25',
                };

                $buckets[$bucket]++;
            }
        }

        return $buckets;
    }

    private function completedQuizAttempts(?string $sectorId): Builder
    {
        return QuizAttempt::query()
            ->whereNotNull('completed_at')
            ->where('choice_max_score', '>', 0)
            ->when($sectorId, fn (Builder $query, string $sectorId) => $query->whereHas(
                'quizContent',
                fn (Builder $q) => $q->forSector($sectorId)
            ));
    }
}
