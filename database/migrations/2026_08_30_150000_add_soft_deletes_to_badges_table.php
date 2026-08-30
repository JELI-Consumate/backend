<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table): void {
            // Index dulu sebelum unique-nya di-drop -- unique index itu yang
            // menopang FK ke journeys, MySQL nolak drop kalau gak ada index
            // pengganti buat kolom yang sama (error 1553).
            $table->index('journey_id');
        });

        Schema::table('badges', function (Blueprint $table): void {
            $table->dropUnique(['journey_id']);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->unique('journey_id');
        });

        Schema::table('badges', function (Blueprint $table): void {
            $table->dropIndex(['journey_id']);
        });
    }
};
