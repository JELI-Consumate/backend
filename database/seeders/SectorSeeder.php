<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed data sektor "E-Commerce" (1 baris), mengikuti snapshot data referensi.
 * icon_url bernilai null, karena aset ikon sektor belum tersedia.
 */
class SectorSeeder extends Seeder
{
    use LoadsContentSnapshot;

    public function run(): void
    {
        $this->seedTable('sectors');
    }
}
