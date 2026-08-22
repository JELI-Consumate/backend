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
            'kenali-hakmu-sebagai-konsumen' => [
                'name' => 'Consumer Rights Explorer',
                'description' => 'Memahami dasar-dasar hak dan kewajiban konsumen.',
            ],
            'belanja-online-dengan-lebih-cerdas' => [
                'name' => 'Smart Shopper',
                'description' => 'Mampu membuat keputusan belanja yang tepat.',
            ],
            'lindungi-dirimu-dari-risiko-digital' => [
                'name' => 'Digital Safety Guardian',
                'description' => 'Mampu melindungi diri dari risiko digital.',
            ],
            'berani-memperjuangkan-hakmu' => [
                'name' => 'Consumer Champion',
                'description' => 'Berani memperjuangkan hak sebagai konsumen.',
            ],
        ];

        foreach ($badges as $journeySlug => $badge) {
            $journey = Journey::withoutGlobalScopes()->where('slug', $journeySlug)->firstOrFail();

            Badge::query()->updateOrCreate(
                ['journey_id' => $journey->id],
                [
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    // TODO(Faqih): ganti dengan URL ikon badge asli, ini placeholder.
                    'icon_url' => "https://placehold.co/256x256?text={$badge['name']}",
                ]
            );
        }
    }
}
