<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\SimulationAttempt;

/**
 * Hasil 1x check jawaban Duolingo-style: jawaban salah TIDAK disimpan dan
 * TIDAK menyelesaikan attempt — user cuma dapat feedback `correct=false`
 * untuk coba lagi item yang sama. Attempt otomatis completed begitu seluruh
 * item (matching + ordering) sudah pernah dijawab benar.
 */
final readonly class SimulationAnswerCheckResult
{
    public function __construct(
        public bool $correct,
        public SimulationAttempt $attempt,
    ) {}
}
