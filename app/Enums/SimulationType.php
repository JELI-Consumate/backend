<?php

declare(strict_types=1);

namespace App\Enums;

enum SimulationType: string
{
    case Matching = 'matching';
    case Ordering = 'ordering';
}
