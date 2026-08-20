<?php

declare(strict_types=1);

namespace App\Support;

final class CacheKey
{
    public static function moduleTree(int $moduleId, string $updatedAt): string
    {
        return "content:module:{$moduleId}:v{$updatedAt}";
    }

    public static function empowermentIndex(int $userId, int $sectorId): string
    {
        return "empowerment-index:user:{$userId}:sector:{$sectorId}";
    }
}
