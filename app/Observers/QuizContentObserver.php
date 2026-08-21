<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\QuizKind;
use App\Enums\QuizSegmentType;
use App\Exceptions\InvalidQuizContentException;
use App\Models\QuizContent;

final class QuizContentObserver
{
    /**
     * BR-04: kind=quiz wajib journey_id terisi & sector_id null;
     * kind in {pretest, posttest} wajib sector_id terisi & journey_id null.
     */
    public function saving(QuizContent $quizContent): void
    {
        if ($quizContent->kind === QuizKind::Quiz) {
            if ($quizContent->journey_id === null || $quizContent->sector_id !== null) {
                throw new InvalidQuizContentException(
                    'Kuis kind "quiz" wajib memiliki journey_id dan sector_id harus null.'
                );
            }

            return;
        }

        if ($quizContent->sector_id === null || $quizContent->journey_id !== null) {
            throw new InvalidQuizContentException(
                'Kuis pretest/posttest wajib memiliki sector_id dan journey_id harus null.'
            );
        }
    }

    /**
     * BR-09: pretest/posttest wajib minimal 2 segment (multiple_choice + likert).
     * Dicek di updated() bukan saving()/created() karena segment anak baru bisa
     * dibuat setelah quiz_content tersimpan (FK ke parent) — create awal (belum
     * ada segment) tidak divalidasi; panggil ulang $quizContent->save() dengan
     * minimal satu atribut berubah setelah seluruh segment lengkap untuk
     * menegakkan aturan ini. Eloquent tidak fire event 'updated' kalau model
     * tidak dirty, jadi save() tanpa perubahan (termasuk touch() dalam detik
     * yang sama) tidak memicu validasi ini.
     */
    public function updated(QuizContent $quizContent): void
    {
        if ($quizContent->kind === QuizKind::Quiz) {
            return;
        }

        $segmentTypes = $quizContent->segments()->pluck('segment_type');

        $hasMultipleChoice = $segmentTypes->contains(QuizSegmentType::MultipleChoice);
        $hasLikert = $segmentTypes->contains(QuizSegmentType::Likert);

        if ($segmentTypes->count() < 2 || ! $hasMultipleChoice || ! $hasLikert) {
            throw new InvalidQuizContentException(
                'Kuis pretest/posttest wajib memiliki minimal 2 segment: multiple_choice dan likert.'
            );
        }
    }
}
