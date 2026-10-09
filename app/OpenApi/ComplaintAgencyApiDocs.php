<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Dokumentasi OpenAPI untuk ComplaintAgencyController dan
 * ComplaintCategoryController. Dipisah dari controller supaya tetap ringkas.
 */
final class ComplaintAgencyApiDocs
{
    #[OA\Get(
        path: '/complaint-agencies',
        summary: 'Daftar lembaga pengaduan aktif (kartu beranda Pusat Pengaduan)',
        tags: ['Pusat Pengaduan'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK, diurutkan sesuai `order`',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/complaint-agencies/{slug}',
        summary: 'Detail lembaga pengaduan + kanal kontak/tombol aksi',
        tags: ['Pusat Pengaduan'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'bpom'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK. `channels[].url` siap dibuka (https, tel:, mailto:, wa.me) dan null untuk kanal info saja (alamat)', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 404, description: 'Lembaga tidak ditemukan / tidak aktif'),
        ]
    )]
    public function show(): void {}

    #[OA\Get(
        path: '/complaint-categories',
        summary: 'Jenis permasalahan konsumen beserta lembaga yang relevan ("Bingung harus mengadu ke mana?")',
        tags: ['Pusat Pengaduan'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK, hanya lembaga aktif yang ikut ter-embed',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function categories(): void {}
}
