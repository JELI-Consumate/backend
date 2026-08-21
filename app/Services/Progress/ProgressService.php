<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Enums\ProgressStatus;
use App\Events\JourneyCompleted;
use App\Events\JourneyProgressRecalculated;
use App\Events\ModuleCompleted;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ProgressService
{
    /**
     * BR-11: idempotent — kalau status sudah completed, tidak diubah lagi.
     */
    public function markPageCompleted(User $user, ModulePage $page): ModuleProgress
    {
        $existing = ModuleProgress::query()
            ->where('user_id', $user->id)
            ->where('module_page_id', $page->id)
            ->first();

        if ($existing?->status === ProgressStatus::Completed) {
            return $existing;
        }

        $progress = ModuleProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'module_page_id' => $page->id],
            ['status' => ProgressStatus::Completed, 'completed_at' => now()],
        );

        $page->loadMissing('module.journey');
        event(new ModuleCompleted($user, $page));

        return $progress;
    }

    /**
     * Simpan posisi terakhir (mis. detik video, indeks slide) — tidak menurunkan
     * status dari completed, dan menaikkan not_started ke in_progress.
     */
    public function updateLastPosition(User $user, ModulePage $page, int $position): ModuleProgress
    {
        $existing = ModuleProgress::query()
            ->where('user_id', $user->id)
            ->where('module_page_id', $page->id)
            ->first();

        $status = $existing?->status === ProgressStatus::Completed
            ? ProgressStatus::Completed
            : ProgressStatus::InProgress;

        return ModuleProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'module_page_id' => $page->id],
            ['last_position' => $position, 'status' => $status],
        );
    }

    /**
     * BR-03: persen = Σ estimated_minutes module wajib yang SELURUH halamannya
     * completed ÷ Σ estimated_minutes seluruh module wajib × 100, floor.
     * "Seluruh halaman completed" dicek via correlated subquery whereDoesntHave
     * bertingkat — bukan loop PHP. Fire JourneyProgressRecalculated selalu (BR-15),
     * dan JourneyCompleted khusus saat status baru berubah jadi completed.
     */
    public function recalculateJourney(User $user, Journey $journey): JourneyProgress
    {
        return DB::transaction(function () use ($user, $journey): JourneyProgress {
            $totalMinutes = (int) $journey->modules()->where('is_required', true)->sum('estimated_minutes');

            $completedMinutes = (int) Module::query()
                ->where('journey_id', $journey->id)
                ->where('is_required', true)
                ->whereDoesntHave('pages', fn ($page) => $page
                    ->whereDoesntHave('userProgress', fn ($p) => $p
                        ->where('user_id', $user->id)
                        ->where('status', ProgressStatus::Completed)))
                ->sum('estimated_minutes');

            $percent = $totalMinutes > 0 ? intdiv($completedMinutes * 100, $totalMinutes) : 0;
            $status = $this->statusFromPercent($percent);

            $existing = JourneyProgress::query()
                ->where('user_id', $user->id)
                ->where('journey_id', $journey->id)
                ->first();

            $wasCompleted = $existing?->status === ProgressStatus::Completed;

            $progress = JourneyProgress::query()->updateOrCreate(
                ['user_id' => $user->id, 'journey_id' => $journey->id],
                [
                    'status' => $status,
                    'progress_percent' => $percent,
                    'completed_at' => $status === ProgressStatus::Completed
                        ? ($existing?->completed_at ?? now())
                        : null,
                ],
            );

            $journey->loadMissing('sector');
            event(new JourneyProgressRecalculated($user, $journey));

            if ($status === ProgressStatus::Completed && ! $wasCompleted) {
                event(new JourneyCompleted($user, $journey));
            }

            return $progress;
        });
    }

    /**
     * BR-14: rata-rata berbobot durasi seluruh journey_progress user dalam sektor,
     * memakai journeys.estimated_minutes yang sudah denormalized (BR-13) — tidak
     * JOIN ke modules/module_pages, cukup ke journeys + journey_progress.
     *
     * Status completed saat seluruh journey published di sektor sudah completed
     * (skema saat ini belum punya `journeys.is_required` — lihat pertanyaan
     * terbuka #6 di 06-nonfunctional-ops.md — jadi seluruh journey dianggap wajib).
     */
    public function recalculateSector(User $user, Sector $sector): SectorProgress
    {
        return DB::transaction(function () use ($user, $sector): SectorProgress {
            $journeys = $sector->journeys()->get(['id', 'estimated_minutes']);

            $totalMinutes = (int) $journeys->sum('estimated_minutes');

            $progressByJourneyId = JourneyProgress::query()
                ->where('user_id', $user->id)
                ->whereIn('journey_id', $journeys->pluck('id'))
                ->get()
                ->keyBy('journey_id');

            $weightedSum = $journeys->sum(
                fn (Journey $journey): int => $journey->estimated_minutes * ($progressByJourneyId->get($journey->id)?->progress_percent ?? 0)
            );

            $percent = $totalMinutes > 0 ? intdiv((int) $weightedSum, $totalMinutes) : 0;

            $allCompleted = $journeys->isNotEmpty() && $journeys->every(
                fn (Journey $journey): bool => $progressByJourneyId->get($journey->id)?->status === ProgressStatus::Completed
            );

            $status = $allCompleted ? ProgressStatus::Completed : $this->statusFromPercent($percent);

            $existing = SectorProgress::query()
                ->where('user_id', $user->id)
                ->where('sector_id', $sector->id)
                ->first();

            return SectorProgress::query()->updateOrCreate(
                ['user_id' => $user->id, 'sector_id' => $sector->id],
                [
                    'status' => $status,
                    'progress_percent' => $percent,
                    'completed_at' => $status === ProgressStatus::Completed
                        ? ($existing?->completed_at ?? now())
                        : null,
                ],
            );
        });
    }

    private function statusFromPercent(int $percent): ProgressStatus
    {
        return match (true) {
            $percent >= 100 => ProgressStatus::Completed,
            $percent > 0 => ProgressStatus::InProgress,
            default => ProgressStatus::NotStarted,
        };
    }
}
