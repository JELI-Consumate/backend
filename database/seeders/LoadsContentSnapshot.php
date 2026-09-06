<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

/**
 * Loader bersama untuk snapshot DB hasil generate
 * (database/seeders/data/content_snapshot.php).
 */
trait LoadsContentSnapshot
{
    /** @return array<string, list<array<string, mixed>>> */
    protected function snapshot(): array
    {
        return require __DIR__.'/data/content_snapshot.php';
    }

    /** Upsert seluruh baris satu tabel snapshot, dikunci lewat ULID. */
    protected function seedTable(string $table): void
    {
        foreach ($this->snapshot()[$table] ?? [] as $row) {
            DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
        }
    }
}
