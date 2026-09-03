<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'simulation_attempts_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('simulation_content_id');
            $table->foreign('simulation_content_id', 'simulation_attempts_simulation_content_id_foreign')->references('id')->on('simulation_contents')->onDelete('cascade');
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('max_score')->nullable();
            $table->boolean('is_passed')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'simulation_content_id', 'completed_at'], 'sim_attempts_user_content_completed_idx');
            $table->index(['simulation_content_id'], 'simulation_attempts_simulation_content_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_attempts');
    }
};
