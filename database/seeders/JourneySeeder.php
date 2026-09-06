<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 4 journey sektor E-Commerce — AUTO-GENERATED dari snapshot DB `jeli`.
 *
 * image_url = key cover di r2 yang sudah ada (journeys/covers/*.jpg);
 * tidak ada file webp cover di media_pembelajaran sehingga dibiarkan.
 * estimated_minutes di-snapshot apa adanya (biasanya diturunkan
 * ModuleObserver, tapi di sini kita simpan nilai final).
 */
class JourneySeeder extends Seeder
{
    use LoadsContentSnapshot;

    public function run(): void
    {
        $this->seedTable('journeys');
    }
}
