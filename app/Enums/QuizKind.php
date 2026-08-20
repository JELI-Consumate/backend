<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizKind: string
{
    case Quiz = 'quiz';
    case Pretest = 'pretest';
    case Posttest = 'posttest';
}
