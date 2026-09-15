<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed data 4 journey sektor E-Commerce, mengikuti snapshot data referensi.
 *
 * image_url memakai key cover yang sudah ada di R2 (journeys/covers/*.jpg);
 * tidak ada file webp cover di media_pembelajaran sehingga dibiarkan.
 * estimated_minutes disimpan sebagai nilai final apa adanya, meskipun
 * pada alur normal nilai ini diturunkan otomatis oleh ModuleObserver.
 */
class JourneySeeder extends Seeder
{
    use LoadsContentSnapshot;

    public function run(): void
    {
        $this->seedTable('journeys');
    }
}
