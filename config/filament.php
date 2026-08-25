<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disk Upload Gambar Konten (06-nonfunctional-ops.md §9.7)
    |--------------------------------------------------------------------------
    |
    | Seluruh FileUpload di Filament (article blocks, simulation matching/
    | ordering) memakai disk ini tanpa perlu ->disk() eksplisit per komponen.
    | Default 'public' untuk dev lokal (butuh `php artisan storage:link`).
    | Set FILAMENT_FILESYSTEM_DISK=r2 di production untuk object storage
    | Cloudflare R2 sesungguhnya.
    |
    */
    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

];
