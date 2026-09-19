<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun untuk 160 responden riset sektor E-Commerce, email SUDAH terverifikasi
 * (email_verified_at diisi saat seeding -- mereka mengisi data lewat form
 * riset, bukan lewat alur registrasi in-app).
 *
 * Sumber data & resolusi konflik (lihat "Data Responden Riset Konsumen_
 * E-commerce - Sheet1.pdf", 161 baris mentah -> 160 baris final):
 * - 1 submission kosong untuk rekiramdhan1902@gmail.com (tanpa nama/no HP)
 *   di-skip, digantikan baris lengkap berikutnya dengan email yang sama.
 * - 2 email dipakai oleh 2 orang BERBEDA dengan data lengkap (kemungkinan
 *   salah ketik responden di form riset). Kolom email unique di database,
 *   jadi keduanya tetap dibuatkan akun dengan email kedua ditambah "2":
 *     hasbyfr30@gmail.com  -> Hasby faturrahman (asli) / hasbyfr302@gmail.com -> Asep nurjaman
 *     rayamedina@apps.ipb.ac.id -> Raya Medina (asli) / rayamedina2@apps.ipb.ac.id -> Herawati Diah
 * - 3 baris tanpa "Nama Lengkap" di sheet (hanya email + password): nama
 *   diisi dari local-part email (mis. "sma.jauhar@gmail.com" -> "sma.jauhar").
 * - Nomor WhatsApp dinormalisasi ke format lokal "0..." (mis. "+62 821-..."
 *   dan "81381503973" tanpa 0 di depan) supaya konsisten & valid terhadap
 *   constraint unique kolom phone; nilainya tetap nomor yang sama, hanya
 *   representasinya yang dirapikan.
 *
 * File data asli (nama, no WhatsApp, password) sengaja TIDAK ikut di-commit
 * -- lihat database/seeders/data/respondents.example.php dan .gitignore.
 */
class RespondentUserSeeder extends Seeder
{
    public function run(): void
    {
        $path = __DIR__.'/data/respondents.php';

        if (! is_file($path)) {
            $this->command?->warn('  file data responden tidak ditemukan, dilewati: '.$path);

            return;
        }

        /** @var list<array{name: string, email: string, phone: ?string, password: string}> $rows */
        $rows = require $path;

        foreach ($rows as $row) {
            $user = User::query()->firstOrNew(['email' => $row['email']]);
            $user->forceFill([
                'name' => $row['name'],
                'phone' => $row['phone'],
                'password' => $row['password'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
            $user->save();
        }

        $this->command?->info('  '.count($rows).' akun responden di-seed dari '.$path);
    }
}
