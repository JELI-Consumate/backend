<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    use LoadsContentSnapshot;
    use UploadsSeedMedia;

    public function run(): void
    {
        $badges = $this->snapshot()['badges'] ?? [];

        $this->uploadSeedMedia(array_column($badges, 'icon_url'));
        $this->seedTable('badges');
    }
}
