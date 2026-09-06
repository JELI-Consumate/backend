<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Sector -> Journey -> Module -> Badge. Keempatnya AUTO-GENERATED — snapshot
     * PERSIS isi DB `jeli` (raw upsert by ULID, lihat database/seeders/data/
     * content_snapshot.php): seluruh modul + konten artikel/video/kuis/simulasi/
     * refleksi ikut di ModuleSeeder. Regenerate snapshot tiap DB berubah.
     * ModuleSeeder juga meng-upload foto .webp bundle ke disk aktif.
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

        // Contoh akun admin sector: hanya bisa akses sector "E-Commerce".
        User::factory()->sectorAdmin(Sector::query()->firstOrFail())->create([
            'name' => 'Admin E-Commerce',
            'email' => 'admin.ecommerce@example.com',
        ]);
    }
}
