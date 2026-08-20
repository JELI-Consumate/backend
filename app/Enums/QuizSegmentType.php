<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizSegmentType: string
{
    case MultipleChoice = 'multiple_choice';
    case Likert = 'likert';
}
