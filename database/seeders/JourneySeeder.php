<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Journey;
use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class JourneySeeder extends Seeder
{
    public function run(): void
    {
        $sector = Sector::query()->where('slug', 'e-commerce')->firstOrFail();

        $journeys = [
            [
                'slug' => 'kenali-hakmu-sebagai-konsumen',
                'title' => 'Kenali Hakmu sebagai Konsumen',
                'description' => 'Pengantar tentang kepraktisan belanja online, risiko dasar e-commerce, serta pengenalan awal mengenai peran konsumen, asas perlindungan hukum, dan keseimbangan hak-kewajiban pembeli.',
                'order' => 1,
            ],
            [
                'slug' => 'belanja-online-dengan-lebih-cerdas',
                'title' => 'Belanja Online dengan Lebih Cerdas',
                'description' => 'Panduan praktis untuk mengenali keaslian reputasi toko, cara menganalisis ulasan pembeli, mengamankan metode pembayaran, menghitung total biaya transaksi, serta pentingnya menyimpan bukti transaksi digital.',
                'order' => 2,
            ],
            [
                'slug' => 'lindungi-dirimu-dari-risiko-digital',
                'title' => 'Lindungi Dirimu dari Risiko Digital',
                'description' => 'Edukasi pencegahan kejahatan siber yang membahas karakteristik metode penipuan (phishing), langkah mengunci keamanan akun, rahasia menjaga kode keamanan OTP, dan cara melindungi privasi data pribadi.',
                'order' => 3,
            ],
            [
                'slug' => 'berani-memperjuangkan-hakmu',
                'title' => 'Berani Memperjuangkan Hakmu',
                'description' => 'Langkah penyelesaian sengketa transaksi secara legal yang berisi tata cara komplain yang sopan, prosedur klaim pengembalian barang (return) atau dana (refund), serta eskalasi laporan melalui layanan pelanggan.',
                'order' => 4,
            ],
        ];

        foreach ($journeys as $journey) {
            Journey::withoutGlobalScopes()->updateOrCreate(
                ['sector_id' => $sector->id, 'slug' => $journey['slug']],
                [
                    'title' => $journey['title'],
                    'description' => $journey['description'],
                    'order' => $journey['order'],
                    'status' => PublishStatus::Published,
                    'published_at' => Carbon::now(),
                ]
            );
        }
    }
}
