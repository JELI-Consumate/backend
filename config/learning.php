<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Indeks Keberdayaan (BR-12)
    |--------------------------------------------------------------------------
    |
    | Bobot skor pengetahuan (choice benar) vs skor sikap (likert
    | dinormalisasi 0-100). Total harus 100.
    |
    */
    'empowerment_index' => [
        'knowledge_weight' => 50,
        'attitude_weight' => 50,
    ],

];
