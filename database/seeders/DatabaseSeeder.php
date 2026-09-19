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
     * Sector -> Journey -> Module -> Badge, keempatnya mengikuti snapshot data
     * referensi (raw upsert by ULID, lihat database/seeders/data/
     * content_snapshot.php): seluruh modul beserta konten artikel/video/kuis/
     * simulasi/refleksi ikut disertakan di ModuleSeeder.
     * ModuleSeeder juga meng-upload foto .webp bundle ke disk aktif.
     *
     * RespondentUserSeeder: 160 akun responden riset sektor E-Commerce, email
     * sudah terverifikasi. Butuh file privat database/seeders/data/
     * respondents.php (di-gitignore, PII) -- dilewatkan dengan aman jika tidak
     * ada, lihat respondents.example.php.
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
            RespondentUserSeeder::class,
        ]);

        // Contoh akun admin sector: hanya bisa akses sector "E-Commerce".
        User::factory()->sectorAdmin(Sector::query()->firstOrFail())->create([
            'name' => 'Admin E-Commerce',
            'email' => 'admin.ecommerce@example.com',
        ]);
    }
}
