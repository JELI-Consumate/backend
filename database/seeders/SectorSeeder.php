<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sektor "E-Commerce" (1 baris) — AUTO-GENERATED dari snapshot DB `jeli`.
 * icon_url null persis seperti di DB (belum ada aset ikon sektor).
 */
class SectorSeeder extends Seeder
{
    use LoadsContentSnapshot;

    public function run(): void
    {
        $this->seedTable('sectors');
    }
}
