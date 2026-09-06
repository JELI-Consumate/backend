<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 1 badge per journey (4 baris) — AUTO-GENERATED dari snapshot DB `jeli`.
 * icon_url = placehold.co bawaan DB (belum ada aset badge).
 */
class BadgeSeeder extends Seeder
{
    use LoadsContentSnapshot;

    public function run(): void
    {
        $this->seedTable('badges');
    }
}
