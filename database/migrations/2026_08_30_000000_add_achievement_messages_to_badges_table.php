<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konten tambahan untuk layar "badge diperoleh" (dipicu saat journey
     * selesai, lihat JourneyCompleted -> AwardJourneyBadge): pesan selamat
     * yang tampil di bawah judul "Pencapaian Baru: {badge}", dan pesan
     * motivasi di bagian bawah sebelum tombol lanjut ke journey berikutnya.
     * Nullable di level DB (bukan backfill paksa) supaya badge lama yang
     * sudah di-seed tanpa kedua kolom ini tidak korup -- diisi lewat form
     * Filament yang mewajibkannya untuk badge baru/yang diedit.
     */
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table): void {
            $table->text('congratulation_message')->nullable()->after('description');
            $table->text('motivational_message')->nullable()->after('congratulation_message');
        });
    }

    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table): void {
            $table->dropColumn(['congratulation_message', 'motivational_message']);
        });
    }
};
