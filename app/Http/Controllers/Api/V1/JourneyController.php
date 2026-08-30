<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProgressStatus;
use App\Enums\QuizKind;
use App\Exceptions\JourneyLockedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\JourneyDetailResource;
use App\Models\Journey;
use App\Models\JourneyProgress;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\QuizContent;
use App\Models\User;
use App\Services\Learning\JourneyAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class JourneyController extends Controller
{
    public function __construct(private readonly JourneyAccessService $journeyAccess) {}

    public function show(Request $request, int $id): JsonResponse
    {
        $journey = Journey::query()->with('modules.pages')->findOrFail($id);

        if (! $this->journeyAccess->isUnlocked($request->user(), $journey)) {
            throw new JourneyLockedException;
        }

        $journey->setAttribute('is_unlocked', true);
        $journey->setAttribute(
            'user_progress',
            JourneyProgress::query()->where('user_id', $request->user()->id)->where('journey_id', $journey->id)->first()
        );

        $this->attachModuleProgress($request->user(), $journey);
        $this->attachQuizScore($request->user(), $journey);

        return ApiResponse::success(new JourneyDetailResource($journey));
    }

    /**
     * Skor kuis evaluasi journey ini (mis. buat kartu "Ringkasan Journey" di
     * layar perayaan begitu journey selesai) -- diambil dari attempt TERAKHIR
     * user (bukan yang terbaik, konsisten dengan cara empowerment index
     * mengambil attempt posttest terakhir), dalam persen 0-100. `null` kalau
     * journey ini tidak punya module kuis, atau user belum pernah
     * menyelesaikan satu attempt pun.
     */
    private function attachQuizScore(User $user, Journey $journey): void
    {
        $quiz = QuizContent::query()
            ->where('journey_id', $journey->id)
            ->where('kind', QuizKind::Quiz)
            ->first();

        $attempt = $quiz === null ? null : QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_content_id', $quiz->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('attempt_number')
            ->first();

        $score = $attempt !== null && $attempt->choice_max_score > 0
            ? (int) round($attempt->choice_score / $attempt->choice_max_score * 100)
            : null;

        $journey->setAttribute('quiz_score', $score);
    }

    /**
     * Tempel status selesai + status kunci per module (dipakai FE untuk checklist
     * di layar detail journey + menentukan "lanjutkan dari modul mana" + module mana
     * yang masih digembok). Tidak ada endpoint ringkas untuk ini, jadi dihitung di
     * sini: 1 query bulk untuk seluruh module_progress user di journey ini, lalu
     * di-keyBy per module_page_id — bukan query per modul (lihat pola yang sama di
     * JourneyAccessService::unlockedMapForSector).
     *
     * Status kunci dihitung SEKALIAN di loop yang sama TANPA query tambahan: module
     * terurut ascending by order, status completed module SEBELUMNYA (dihitung di
     * iterasi sebelumnya) menentukan apakah module SAAT INI terkunci -- pola yang
     * sama dengan ModuleAccessService::isUnlocked, cuma di sini versi bulk-nya.
     */
    private function attachModuleProgress(User $user, Journey $journey): void
    {
        $modules = $journey->modules->sortBy('order')->values();
        $pageIds = $modules->flatMap(fn (Module $module) => $module->pages->pluck('id'));

        $progressByPageId = ModuleProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('module_page_id', $pageIds)
            ->get()
            ->keyBy('module_page_id');

        $previousCompleted = true;

        $modules->each(function (Module $module) use ($progressByPageId, &$previousCompleted): void {
            $module->pages->each(
                fn (ModulePage $page) => $page->setAttribute('user_progress', $progressByPageId->get($page->id))
            );

            $totalPages = $module->pages->count();

            $completedPages = $module->pages->filter(
                fn (ModulePage $page) => $progressByPageId->get($page->id)?->status === ProgressStatus::Completed
            )->count();

            $hasStarted = $module->pages->contains(fn (ModulePage $page) => $progressByPageId->has($page->id));

            $status = match (true) {
                $totalPages > 0 && $completedPages === $totalPages => ProgressStatus::Completed,
                $hasStarted => ProgressStatus::InProgress,
                default => ProgressStatus::NotStarted,
            };

            $module->setAttribute('progress', [
                'status' => $status->value,
                'percent' => $totalPages > 0 ? intdiv($completedPages * 100, $totalPages) : 0,
            ]);

            $module->setAttribute('locked', ! $previousCompleted);
            $previousCompleted = $status === ProgressStatus::Completed;
        });
    }
}
