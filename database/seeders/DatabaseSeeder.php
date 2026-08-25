<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan wajib sesuai foreign key & business rule (BR-13 estimated_minutes,
     * BR-04 quiz kind): Sector -> Journey -> Module (+content) -> Badge.
     * Tidak pakai WithoutModelEvents supaya ModuleObserver/QuizContentObserver
     * tetap terpicu (lihat 06-nonfunctional-ops.md §12 & docblock ModuleSeeder).
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            SectorSeeder::class,
            JourneySeeder::class,
            ModuleSeeder::class,
            BadgeSeeder::class,
        ]);
    }
}
