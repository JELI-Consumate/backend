<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Journey;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // Konten asli (lihat layar "badge diperoleh" Journey 1) -- sisanya
            // masih placeholder sampai materinya di-sinkronkan seperti journey ini.
            'kenali-hakmu-sebagai-konsumen' => [
                'name' => 'Consumer Rights Explorer (Penjelajah Hak Konsumen)',
                'description' => 'Badge ini diberikan sebagai penghargaan resmi bagi pengguna yang telah menyelesaikan seluruh lembar materi harian, simulasi interaktif, kuis evaluasi, serta pengisian rapor mandiri pada Journey 1. Sebagai seorang Consumer Rights Explorer, kamu kini telah dibekali fondasi kesadaran hukum yang kuat untuk menjadi pembeli yang lebih aman, bertanggung jawab, dan tidak mudah diremehkan oleh pihak penjual.',
                'congratulation_message' => 'Selamat! Kamu telah berhasil menuntaskan seluruh tantangan pada Journey 1: Kenali Hakmu sebagai Konsumen. Langkah awal ini telah membantumu memahami esensi peran kita di dunia digital, cara menyeimbangkan hak istimewa dengan kewajiban pembeli, serta pentingnya menerapkan sikap teliti sebelum memutuskan untuk melakukan transaksi pembayaran di e-commerce.',
                'motivational_message' => 'Mengetahui cara kerja hukum perlindungan konsumen adalah langkah pertama yang paling krusial untuk mengaktifkan perisai keselamatan belanjamu. Yuk, ambil langkah selanjutnya dan mari pelajari strategi jitu menyaring reputasi toko digital pada Journey 2!',
            ],
            'belanja-online-dengan-lebih-cerdas' => [
                'name' => 'Smart Shopper',
                'description' => 'Mampu membuat keputusan belanja yang tepat.',
                // TODO(Faqih): ganti dengan pesan ucapan selamat & motivasi asli, ini placeholder.
                'congratulation_message' => 'Selamat! Kamu telah berhasil menuntaskan seluruh tantangan pada Journey 2.',
                'motivational_message' => 'Yuk, lanjutkan perjalananmu ke journey berikutnya!',
            ],
            'lindungi-dirimu-dari-risiko-digital' => [
                'name' => 'Digital Safety Guardian',
                'description' => 'Mampu melindungi diri dari risiko digital.',
                // TODO(Faqih): ganti dengan pesan ucapan selamat & motivasi asli, ini placeholder.
                'congratulation_message' => 'Selamat! Kamu telah berhasil menuntaskan seluruh tantangan pada Journey 3.',
                'motivational_message' => 'Yuk, lanjutkan perjalananmu ke journey berikutnya!',
            ],
            'berani-memperjuangkan-hakmu' => [
                'name' => 'Consumer Champion',
                'description' => 'Berani memperjuangkan hak sebagai konsumen.',
                // TODO(Faqih): ganti dengan pesan ucapan selamat & motivasi asli, ini placeholder.
                'congratulation_message' => 'Selamat! Kamu telah berhasil menuntaskan seluruh tantangan pada Journey 4.',
                'motivational_message' => 'Kamu telah menyelesaikan seluruh journey -- teruslah jadi konsumen yang cerdas!',
            ],
        ];

        foreach ($badges as $journeySlug => $badge) {
            $journey = Journey::withoutGlobalScopes()->where('slug', $journeySlug)->firstOrFail();

            Badge::query()->updateOrCreate(
                ['journey_id' => $journey->id],
                [
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    'congratulation_message' => $badge['congratulation_message'],
                    'motivational_message' => $badge['motivational_message'],
                    // TODO(Faqih): ganti dengan URL ikon badge asli, ini placeholder.
                    'icon_url' => "https://placehold.co/256x256?text={$badge['name']}",
                ]
            );
        }
    }
}
