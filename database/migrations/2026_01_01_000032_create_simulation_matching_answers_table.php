<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_matching_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('simulation_attempt_id');
            $table->foreign('simulation_attempt_id', 'simulation_matching_answers_simulation_attempt_id_foreign')->references('id')->on('simulation_attempts')->onDelete('cascade');
            $table->ulid('simulation_matching_pair_id');
            $table->foreign('simulation_matching_pair_id', 'simulation_matching_answers_simulation_matching_pair_id_foreign')->references('id')->on('simulation_matching_pairs')->onDelete('cascade');
            $table->ulid('submitted_right_pair_id');
            $table->foreign('submitted_right_pair_id', 'simulation_matching_answers_submitted_right_pair_id_foreign')->references('id')->on('simulation_matching_pairs')->onDelete('cascade');
            $table->boolean('is_correct');
            $table->timestamps();
            $table->unique(['simulation_attempt_id', 'simulation_matching_pair_id'], 'sim_matching_answers_attempt_pair_unique');
            $table->index(['simulation_matching_pair_id'], 'simulation_matching_answers_simulation_matching_pair_id_foreign');
            $table->index(['submitted_right_pair_id'], 'simulation_matching_answers_submitted_right_pair_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_matching_answers');
    }
};
