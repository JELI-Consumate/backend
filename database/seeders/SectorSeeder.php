<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        Sector::query()->updateOrCreate(
            ['slug' => 'e-commerce'],
            [
                'name' => 'E-Commerce',
                'description' => 'Edukasi perlindungan konsumen untuk transaksi jual-beli online (e-commerce), mencakup hak & kewajiban konsumen, cara berbelanja aman, perlindungan dari penipuan digital, dan prosedur penyelesaian sengketa.',
                'icon_url' => null,
                'color' => null,
                'order' => 1,
                'is_active' => true,
            ]
        );
    }
}
