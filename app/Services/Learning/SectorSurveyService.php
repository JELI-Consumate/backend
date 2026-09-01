<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Enums\SurveyKind;
use App\Exceptions\SurveyNotConfiguredException;
use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;

/**
 * Survei eksternal (Google Form) pretest/posttest per sektor -- terpisah
 * dari kuis in-app QuizContent kind pretest/posttest. Google Form tidak
 * bisa lapor balik ke backend, jadi "selesai" di sini murni self-report:
 * user menekan tombol di app setelah membuka link, tidak ada verifikasi
 * bahwa formnya benar-benar terisi.
 */
final readonly class SectorSurveyService
{
    /**
     * Idempotent seperti ProgressService::markPageCompleted -- menandai ulang
     * tidak menggeser completed_at yang sudah ada.
     */
    public function markCompleted(User $user, Sector $sector, SurveyKind $kind): SectorProgress
    {
        if ($this->linkFor($sector, $kind) === null) {
            throw new SurveyNotConfiguredException(
                match ($kind) {
                    SurveyKind::Pretest => 'Sektor ini belum punya link survei pretest.',
                    SurveyKind::Posttest => 'Sektor ini belum punya link survei posttest.',
                }
            );
        }

        $column = $this->columnFor($kind);

        $existing = SectorProgress::query()
            ->where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();

        if ($existing?->{$column} !== null) {
            return $existing;
        }

        return SectorProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'sector_id' => $sector->id],
            [$column => now()],
        );
    }

    private function linkFor(Sector $sector, SurveyKind $kind): ?string
    {
        return match ($kind) {
            SurveyKind::Pretest => $sector->pretest_survey_link,
            SurveyKind::Posttest => $sector->posttest_survey_link,
        };
    }

    private function columnFor(SurveyKind $kind): string
    {
        return match ($kind) {
            SurveyKind::Pretest => 'pretest_survey_completed_at',
            SurveyKind::Posttest => 'posttest_survey_completed_at',
        };
    }
}
