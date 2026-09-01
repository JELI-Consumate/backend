<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Perlindungan Konsumen API',
    description: 'API backend aplikasi edukasi perlindungan konsumen (mobile + web). Seluruh endpoint (kecuali `/auth/register`, `/auth/login`, `/auth/google`) butuh Sanctum bearer token — lihat skema keamanan `sanctum`. Response sukses selalu berbentuk `{"data": ..., "meta": {...}}`; response error `{"message": ..., "errors": ..., "code": ...}`.'
)]
#[OA\Server(url: '/api/v1', description: 'API v1')]
#[OA\Tag(name: 'Autentikasi', description: 'Register, login (email/telepon atau Google), profil')]
#[OA\Tag(name: 'Katalog Pembelajaran', description: 'Sektor, journey, module, module page')]
#[OA\Tag(name: 'Progres', description: 'Tandai halaman selesai, posisi terakhir, ringkasan progres')]
#[OA\Tag(name: 'Kuis', description: 'Pretest/posttest/kuis journey, attempt, submit jawaban')]
#[OA\Tag(name: 'Simulasi', description: 'Skenario matching/ordering, attempt, submit jawaban')]
#[OA\Tag(name: 'Refleksi', description: 'Jurnal refleksi open-ended/checklist')]
#[OA\Tag(name: 'Gamifikasi', description: 'Badge per journey dan Indeks Keberdayaan per sektor')]
final class OpenApiSpec {}
