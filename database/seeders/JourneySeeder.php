<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed data 4 journey sektor E-Commerce, mengikuti snapshot data referensi.
 *
 * image_url = cover .webp bundle di database/seeders/media/covers/
 * (lihat UploadsSeedMedia), di-upload ke disk aktif saat seeding.
 * estimated_minutes di-snapshot apa adanya (biasanya diturunkan
 * ModuleObserver, tapi di sini kita simpan nilai final).
 */
class JourneySeeder extends Seeder
{
    use LoadsContentSnapshot;
    use UploadsSeedMedia;

    public function run(): void
    {
        $journeys = $this->snapshot()['journeys'] ?? [];

        $this->uploadSeedMedia(array_column($journeys, 'image_url'));
        $this->seedTable('journeys');
    }
}
