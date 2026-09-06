<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Support\Facades\Storage;

/**
 * Upload file .webp bundle (database/seeders/media/) ke disk aktif saat seeding.
 * Key = nilai kolom image_url, mis. "media_pembelajaran/journey2/simulation/scenario-1.webp".
 */
trait UploadsSeedMedia
{
    private const SEED_MEDIA_PREFIX = 'media_pembelajaran/';

    /**
     * @param  iterable<int, string|null>  $keys
     */
    protected function uploadSeedMedia(iterable $keys): void
    {
        $disk = config('filament.default_filesystem_disk', 'public');
        $seen = [];

        foreach ($keys as $key) {
            if (! is_string($key) || ! str_starts_with($key, self::SEED_MEDIA_PREFIX) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $source = __DIR__.'/media/'.substr($key, strlen(self::SEED_MEDIA_PREFIX));

            if (! is_file($source)) {
                $this->command?->warn("  media hilang, dilewati: {$source}");

                continue;
            }

            Storage::disk($disk)->put($key, (string) file_get_contents($source), 'public');
            $this->command?->info("  upload [{$disk}] {$key}");
        }
    }
}
