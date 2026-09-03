<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'sector_progress_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('sector_id');
            $table->foreign('sector_id', 'sector_progress_sector_id_foreign')->references('id')->on('sectors')->onDelete('cascade');
            $table->string('status', 20)->default('not_started');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('pretest_survey_completed_at')->nullable();
            $table->timestamp('posttest_survey_completed_at')->nullable();
            $table->timestamps();
            $table->index(['sector_id'], 'sector_progress_sector_id_foreign');
            $table->unique(['user_id', 'sector_id'], 'sector_progress_user_id_sector_id_unique');
            $table->index(['user_id', 'status'], 'sector_progress_user_id_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_progress');
    }
};
