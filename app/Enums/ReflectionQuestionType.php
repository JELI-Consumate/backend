<?php

declare(strict_types=1);

namespace App\Enums;

enum ReflectionQuestionType: string
{
    case OpenQuestion = 'open_question';
    case Checklist = 'checklist';
}
