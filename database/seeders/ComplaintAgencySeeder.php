<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ComplaintAgency;
use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bootstrap lembaga pengaduan + jenis permasalahan dari dokumen riset (lihat
 * database/seeders/data/complaint_agencies.php). Hanya MEMBUAT yang belum ada:
 * lembaga dikenali lewat slug, kategori lewat teks `problem`. Baris yang
 * sudah ada (termasuk yang disembunyikan/dihapus admin) tidak disentuh --
 * teks, kanal, urutan, status, dan pivot kategori tetap seperti hasil edit
 * di Filament. Aman dijalankan ulang di production.
 *
 * Satu-satunya pengisian ulang: logo (database/seeders/media/complaint-agencies
 * /{slug}.webp) di-upload ke disk aktif untuk lembaga yang logo_url-nya masih
 * kosong, jadi logo yang sudah diganti admin tidak tertimpa.
 *
 * Tidak dipanggil dari DatabaseSeeder. Jalankan manual:
 * php artisan db:seed --class=ComplaintAgencySeeder
 */
class ComplaintAgencySeeder extends Seeder
{
    use UploadsSeedMedia;

    public function run(): void
    {
        /** @var array{agencies: list<array<string, mixed>>, categories: list<array<string, mixed>>} $data */
        $data = require database_path('seeders/data/complaint_agencies.php');

        DB::transaction(function () use ($data): void {
            $agencyIds = [];

            foreach ($data['agencies'] as $index => $row) {
                $channels = $row['channels'];
                unset($row['channels']);

                $agency = ComplaintAgency::withTrashed()->firstOrNew(['slug' => $row['slug']]);

                if (! $agency->exists) {
                    $agency->fill([...$row, 'order' => $index + 1, 'is_active' => true])->save();

                    foreach ($channels as $channelIndex => $channel) {
                        $agency->channels()->create([...$channel, 'order' => $channelIndex + 1]);
                    }
                }

                $this->seedLogo($agency);

                $agencyIds[$row['slug']] = $agency->id;
            }

            foreach ($data['categories'] as $index => $row) {
                $category = ComplaintCategory::withTrashed()->firstOrNew(['problem' => $row['problem']]);

                if ($category->exists) {
                    continue;
                }

                $category->fill(['order' => $index + 1, 'is_active' => true])->save();

                $category->agencies()->attach(
                    collect($row['agencies'])
                        ->values()
                        ->mapWithKeys(fn (string $slug, int $position) => [$agencyIds[$slug] => ['order' => $position + 1]])
                        ->all()
                );
            }
        });
    }

    private function seedLogo(ComplaintAgency $agency): void
    {
        if ($agency->logo_url !== null) {
            return;
        }

        $key = "media_pembelajaran/complaint-agencies/{$agency->slug}.webp";

        if (! is_file(__DIR__.'/media/complaint-agencies/'.$agency->slug.'.webp')) {
            return;
        }

        $this->uploadSeedMedia([$key]);
        $agency->update(['logo_url' => $key]);
    }
}
