<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis survei eksternal (Google Form) per sektor -- lihat SectorSurveyService.
 * Tidak berkaitan dengan QuizKind::Pretest/Posttest (kuis in-app yang
 * memberi skor untuk Indeks Keberdayaan); ini murni link + tracking selesai.
 */
enum SurveyKind: string
{
    case Pretest = 'pretest';
    case Posttest = 'posttest';
}
