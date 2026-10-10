<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ComplaintAgency;
use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed lembaga pengaduan + jenis permasalahan dari dokumen riset (lihat
 * database/seeders/data/complaint_agencies.php). Idempotent: lembaga dikunci
 * per slug, kategori per teks `problem`, kanal di-replace seluruhnya.
 *
 * Logo (database/seeders/media/complaint-agencies/{slug}.webp) di-upload ke
 * disk aktif HANYA untuk lembaga yang logo_url-nya masih kosong, jadi logo
 * yang sudah diganti admin lewat Filament tidak tertimpa.
 *
 * Tidak dipanggil dari DatabaseSeeder: konten ini diedit admin, jadi
 * menjalankannya ulang akan menimpa perubahan manual. Jalankan manual:
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

                $agency = ComplaintAgency::withTrashed()->updateOrCreate(
                    ['slug' => $row['slug']],
                    [...$row, 'order' => $index + 1, 'is_active' => true],
                );
                $agency->restore();

                $this->seedLogo($agency);

                $agency->channels()->delete();
                foreach ($channels as $channelIndex => $channel) {
                    $agency->channels()->create([...$channel, 'order' => $channelIndex + 1]);
                }

                $agencyIds[$row['slug']] = $agency->id;
            }

            foreach ($data['categories'] as $index => $row) {
                $category = ComplaintCategory::withTrashed()->updateOrCreate(
                    ['problem' => $row['problem']],
                    ['order' => $index + 1, 'is_active' => true],
                );
                $category->restore();

                $category->agencies()->sync(
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
