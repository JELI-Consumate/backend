<?php

declare(strict_types=1);

/**
 * Contoh bentuk database/seeders/data/respondents.php -- file ASLI berisi data
 * pribadi responden riset (nama, no WhatsApp, password) sehingga di-gitignore
 * dan TIDAK ikut ter-commit ke repo publik (lihat .gitignore).
 *
 * Untuk seed data responden sungguhan: salin file ini ke respondents.php lalu
 * isi dengan data asli, atau salin langsung file respondents.php yang sudah
 * disiapkan (dibagikan lewat kanal terpisah, bukan git) ke path yang sama.
 * Tanpa file itu, RespondentUserSeeder akan melewatkan langkah ini dengan aman
 * (lihat pesan "file data responden tidak ditemukan, dilewati").
 *
 * - phone nullable (beberapa baris riset tidak mencantumkan no WhatsApp).
 * - password di sini plain text -- akan di-hash otomatis oleh cast
 *   'password' => 'hashed' di App\Models\User saat disimpan.
 */

return [
    [
        'name' => 'Nama Responden Contoh',
        'email' => 'responden.contoh@example.com',
        'phone' => '081234567890',
        'password' => 'CONTOH123',
    ],
    [
        'name' => 'responden.tanpa.nomor',
        'email' => 'responden.tanpa.nomor@example.com',
        'phone' => null,
        'password' => 'CONTOH456',
    ],
];
