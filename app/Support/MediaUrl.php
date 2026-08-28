<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mengubah path relatif yang disimpan Filament FileUpload (mis.
 * "articles/blocks/xxxx.jpg") jadi URL absolut yang benar-benar bisa dimuat
 * app mobile. Filament menyimpan itu apa adanya (disk-agnostic) lewat disk
 * default (`FILAMENT_FILESYSTEM_DISK`, di production diset ke `r2` —
 * Cloudflare R2, lihat config/filesystems.php & config/filament.php) — jadi
 * resolusi URL-nya pun harus ikut disk yang sama itu, bukan diasumsikan
 * selalu disk lokal.
 *
 * Dua jalur berbeda sengaja dipisah:
 * - Disk cloud (r2/s3/dst.) -- endpoint publiknya (mis. R2_PUBLIC_URL) tetap
 *   sama terlepas dari host mana client mengakses API, jadi URL bawaan
 *   disk itu sendiri (Storage::url()) sudah benar apa adanya.
 * - Disk lokal ("public", dipakai kalau kredensial R2 belum diisi mis. di
 *   mesin dev kontributor lain) -- Storage::disk('public')->url() memakai
 *   APP_URL statis dari config (mis. "http://localhost:8000"), yang dari
 *   sisi Android emulator/HP fisik tidak bisa diresolve (mereka mengakses
 *   API lewat host lain, mis. 10.0.2.2 atau IP LAN). url() di sini malah
 *   mengikuti host yang benar-benar dipakai request yang sedang berjalan.
 */
final class MediaUrl
{
    public static function resolve(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $disk = config('filament.default_filesystem_disk', 'public');

        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            return url('storage/'.ltrim($path, '/'));
        }

        return Storage::disk($disk)->url($path);
    }
}
