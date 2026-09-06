<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seluruh konten sektor E-Commerce — AUTO-GENERATED, sama persis isi DB `jeli`
 * (lihat database/seeders/data/content_snapshot.php).
 *
 * Menyeed (upsert by ULID, urutan aman FK): video / article (+ blocks) / quiz
 * (+ segments + questions + options) / simulation (+ matching pairs + ordering
 * steps) / reflection (+ sections + questions + checklist items), lalu modules
 * & module_pages.
 *
 * Foto: setiap image_url / *_image_url yang diawali "media_pembelajaran/"
 * merujuk file .webp di database/seeders/media/. File itu di-upload ke disk
 * aktif (r2 di production, public di lokal) saat seeding — MediaUrl::resolve
 * yang mengubahnya jadi URL absolut. Nilai gambar lain (r2 key, placehold.co)
 * disimpan apa adanya.
 *
 * Sengaja query builder (bukan Eloquent) supaya observer tidak ikut jalan:
 * nilai turunan (estimated_minutes, quiz.kind) sudah final di snapshot.
 */
class ModuleSeeder extends Seeder
{
    use LoadsContentSnapshot;
    use UploadsSeedMedia;

    /** Kolom bergambar per tabel snapshot. */
    private const IMAGE_COLUMNS = [
        'article_blocks' => ['image_url'],
        'simulation_matching_pairs' => ['left_image_url', 'right_image_url'],
        'simulation_ordering_steps' => ['image_url'],
    ];

    /** Urutan insert aman terhadap foreign key. */
    private const TABLE_ORDER = [
        'video_contents',
        'article_contents',
        'quiz_contents',
        'simulation_contents',
        'reflection_contents',
        'article_blocks',
        'quiz_segments',
        'simulation_matching_pairs',
        'simulation_ordering_steps',
        'reflection_sections',
        'quiz_questions',
        'reflection_questions',
        'quiz_choice_options',
        'reflection_checklist_items',
        'modules',
        'module_pages',
    ];

    public function run(): void
    {
        $snapshot = $this->snapshot();

        $this->uploadSeedMedia($this->mediaKeys($snapshot));

        foreach (self::TABLE_ORDER as $table) {
            foreach ($snapshot[$table] ?? [] as $row) {
                DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
            }
        }
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $snapshot
     * @return list<string>
     */
    private function mediaKeys(array $snapshot): array
    {
        $keys = [];

        foreach (self::IMAGE_COLUMNS as $table => $columns) {
            foreach ($snapshot[$table] ?? [] as $row) {
                foreach ($columns as $column) {
                    if (isset($row[$column])) {
                        $keys[] = $row[$column];
                    }
                }
            }
        }

        return $keys;
    }
}
