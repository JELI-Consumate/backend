<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracking sudah/belumnya user mengisi survei pretest/posttest eksternal
     * (Google Form) per sektor. Google Form tidak melapor balik ke backend,
     * jadi ini self-report: user menandai selesai lewat endpoint khusus
     * setelah membuka link-nya -- lihat SectorSurveyService.
     */
    public function up(): void
    {
        Schema::table('sector_progress', function (Blueprint $table): void {
            $table->timestamp('pretest_survey_completed_at')->nullable()->after('completed_at');
            $table->timestamp('posttest_survey_completed_at')->nullable()->after('pretest_survey_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('sector_progress', function (Blueprint $table): void {
            $table->dropColumn(['pretest_survey_completed_at', 'posttest_survey_completed_at']);
        });
    }
};
