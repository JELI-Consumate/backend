<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed data badge, satu badge per journey (4 baris), mengikuti snapshot database referensi.
 * icon_url masih memakai placehold.co karena aset badge belum tersedia.
 */
class BadgeSeeder extends Seeder
{
    use LoadsContentSnapshot;

    public function run(): void
    {
        $this->seedTable('badges');
    }
}
