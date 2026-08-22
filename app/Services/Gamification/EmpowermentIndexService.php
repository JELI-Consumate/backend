<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\QuizKind;
use App\Models\QuizAttempt;
use App\Models\Sector;
use App\Models\User;
use App\Support\CacheKey;
use Illuminate\Support\Facades\Cache;

final readonly class EmpowermentIndexService
{
    /**
     * BR-12: 50% skor pengetahuan (persentase choice benar) + 50% skor sikap
     * (likert_average dinormalisasi 0-100). Sumber: attempt posttest terakhir
     * sektor; fallback ke pretest kalau posttest belum ada. Bobot disimpan di
     * config/learning.php, bukan hardcode. Cache 15 menit per user, diforget
     * lewat listener saat attempt pretest/posttest baru completed.
     */
    public function calculate(User $user, Sector $sector): int
    {
        $cacheKey = CacheKey::empowermentIndex($user->id, $sector->id);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $sector): int {
            $attempt = $this->lastAttempt($user, $sector, QuizKind::Posttest)
                ?? $this->lastAttempt($user, $sector, QuizKind::Pretest);

            if ($attempt === null) {
                return 0;
            }

            $knowledgeScore = $attempt->choice_max_score > 0
                ? intdiv($attempt->choice_score * 100, $attempt->choice_max_score)
                : 0;

            $attitudeScore = $this->normalizeLikert((float) $attempt->likert_average);

            $weights = config('learning.empowerment_index');

            $index = ($weights['knowledge_weight'] * $knowledgeScore + $weights['attitude_weight'] * $attitudeScore) / 100;

            return (int) round($index);
        });
    }

    private function lastAttempt(User $user, Sector $sector, QuizKind $kind): ?QuizAttempt
    {
        return QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->whereHas(
                'quizContent',
                fn ($query) => $query->where('sector_id', $sector->id)->where('kind', $kind)
            )
            ->orderByDesc('completed_at')
            ->first();
    }

    private function normalizeLikert(?float $likertAverage): float
    {
        if ($likertAverage === null) {
            return 0.0;
        }

        $min = config('learning.empowerment_index.likert_min');
        $max = config('learning.empowerment_index.likert_max');

        $range = $max - $min;

        if ($range <= 0) {
            return 0.0;
        }

        return (($likertAverage - $min) / $range) * 100;
    }
}
