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
 * per slug, kategori per teks `problem`, kanal di-replace seluruhnya. Logo
 * sengaja null -- diunggah admin lewat Filament.
 *
 * Tidak dipanggil dari DatabaseSeeder: konten ini diedit admin, jadi
 * menjalankannya ulang akan menimpa perubahan manual. Jalankan manual:
 * php artisan db:seed --class=ComplaintAgencySeeder
 */
class ComplaintAgencySeeder extends Seeder
{
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
}
