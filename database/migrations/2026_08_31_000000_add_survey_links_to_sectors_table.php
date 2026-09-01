<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link Google Form pretest/posttest per sektor, diisi admin lewat
     * Filament. Terpisah dari sistem kuis in-app (QuizContent kind
     * pretest/posttest yang memberi skor untuk Indeks Keberdayaan) -- ini
     * survei eksternal murni, nullable karena tidak semua sektor punya
     * survei sampai admin menambahkannya.
     */
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table): void {
            $table->string('pretest_survey_link')->nullable()->after('color');
            $table->string('posttest_survey_link')->nullable()->after('pretest_survey_link');
        });
    }

    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table): void {
            $table->dropColumn(['pretest_survey_link', 'posttest_survey_link']);
        });
    }
};
