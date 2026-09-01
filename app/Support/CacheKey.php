<?php

declare(strict_types=1);

namespace App\Support;

final class CacheKey
{
    public static function moduleTree(string $moduleId, string $updatedAt): string
    {
        return "content:module:{$moduleId}:v{$updatedAt}";
    }

    public static function empowermentIndex(string $userId, string $sectorId): string
    {
        return "empowerment-index:user:{$userId}:sector:{$sectorId}";
    }
}
