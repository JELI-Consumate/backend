<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\ProgressStatus;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use App\Services\Gamification\EmpowermentIndexService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Halaman kustom "Pengguna & Analitik" (06-nonfunctional-ops.md §10): user
 * aktif, tingkat penyelesaian per journey, rata-rata skor kuis, distribusi
 * indeks keberdayaan. Query agregat sederhana — halaman ini dibuka jarang
 * oleh peneliti, bukan endpoint publik berbudget ketat seperti §8.
 */
class LearningAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Learning Analytics';

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna & Analitik';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.learning-analytics';

    public int $activeUsersCount = 0;

    /** @var array<int, array{title: string, total: int, completed: int, percent: int}> */
    public array $journeyCompletion = [];

    public ?float $averageQuizScore = null;

    /** @var array{'0-25': int, '25-50': int, '50-75': int, '75-100': int} */
    public array $empowermentIndexDistribution = ['0-25' => 0, '25-50' => 0, '50-75' => 0, '75-100' => 0];

    public function mount(EmpowermentIndexService $empowermentIndex): void
    {
        $this->activeUsersCount = ModuleProgress::query()
            ->where('updated_at', '>=', now()->subDays(30))
            ->distinct('user_id')
            ->count('user_id');

        $this->journeyCompletion = Journey::withoutGlobalScopes()
            ->get(['id', 'title'])
            ->map(function (Journey $journey): array {
                $total = JourneyProgress::query()->where('journey_id', $journey->id)->count();
                $completed = JourneyProgress::query()
                    ->where('journey_id', $journey->id)
                    ->where('status', ProgressStatus::Completed)
                    ->count();

                return [
                    'title' => $journey->title,
                    'total' => $total,
                    'completed' => $completed,
                    'percent' => $total > 0 ? (int) round($completed * 100 / $total) : 0,
                ];
            })
            ->all();

        $this->averageQuizScore = round((float) QuizAttempt::query()
            ->whereNotNull('completed_at')
            ->where('choice_max_score', '>', 0)
            ->selectRaw('AVG(choice_score * 100.0 / choice_max_score) as avg_pct')
            ->value('avg_pct'), 1);

        $this->empowermentIndexDistribution = $this->calculateDistribution($empowermentIndex);
    }

    /**
     * @return array{'0-25': int, '25-50': int, '50-75': int, '75-100': int}
     */
    private function calculateDistribution(EmpowermentIndexService $empowermentIndex): array
    {
        $buckets = ['0-25' => 0, '25-50' => 0, '50-75' => 0, '75-100' => 0];

        $userIds = SectorProgress::query()->distinct()->pluck('user_id');
        $sectors = Sector::query()->active()->get();

        if ($userIds->isEmpty() || $sectors->isEmpty()) {
            return $buckets;
        }

        $users = User::query()->whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            foreach ($sectors as $sector) {
                $index = $empowermentIndex->calculate($user, $sector);

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
}
