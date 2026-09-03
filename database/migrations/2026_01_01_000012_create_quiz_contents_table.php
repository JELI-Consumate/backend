<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_contents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('kind');
            $table->ulid('journey_id')->nullable();
            $table->foreign('journey_id', 'quiz_contents_journey_id_foreign')->references('id')->on('journeys')->onDelete('cascade');
            $table->ulid('sector_id')->nullable();
            $table->foreign('sector_id', 'quiz_contents_sector_id_foreign')->references('id')->on('sectors')->onDelete('cascade');
            $table->unsignedTinyInteger('passing_score')->default(70);
            $table->boolean('shuffle_questions')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['journey_id'], 'quiz_contents_journey_id_foreign');
            $table->index(['kind', 'journey_id'], 'quiz_contents_kind_journey_id_index');
            $table->index(['kind', 'sector_id'], 'quiz_contents_kind_sector_id_index');
            $table->index(['sector_id'], 'quiz_contents_sector_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_contents');
    }
};
