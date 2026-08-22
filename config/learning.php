<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Indeks Keberdayaan
    |--------------------------------------------------------------------------
    |
    | Bobot skor pengetahuan (choice benar) vs skor sikap (likert
    | dinormalisasi 0-100). Total harus 100.
    |
    */
    'empowerment_index' => [
        'knowledge_weight' => 50,
        'attitude_weight' => 50,

        /*
        | Rentang nilai skala Likert dipakai untuk normalisasi likert_average
        | (quiz_attempts) ke 0-100. Pertanyaan terbuka #2 (06-nonfunctional-ops.md
        | kalau keputusan final beda.
        */
        'likert_min' => 1,
        'likert_max' => 5,
    ],

];
