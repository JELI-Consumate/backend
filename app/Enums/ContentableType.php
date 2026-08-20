<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentableType: string
{
    case Video = 'video';
    case Article = 'article';
    case Quiz = 'quiz';
    case Simulation = 'simulation';
    case Reflection = 'reflection';
}
