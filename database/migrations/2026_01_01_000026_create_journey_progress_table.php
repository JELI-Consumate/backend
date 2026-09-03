<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'journey_progress_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('journey_id');
            $table->foreign('journey_id', 'journey_progress_journey_id_foreign')->references('id')->on('journeys')->onDelete('cascade');
            $table->string('status', 20)->default('not_started');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['journey_id'], 'journey_progress_journey_id_foreign');
            $table->unique(['user_id', 'journey_id'], 'journey_progress_user_id_journey_id_unique');
            $table->index(['user_id', 'status'], 'journey_progress_user_id_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_progress');
    }
};
