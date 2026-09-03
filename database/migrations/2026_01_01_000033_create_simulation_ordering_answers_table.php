<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_ordering_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('simulation_attempt_id');
            $table->foreign('simulation_attempt_id', 'simulation_ordering_answers_simulation_attempt_id_foreign')->references('id')->on('simulation_attempts')->onDelete('cascade');
            $table->ulid('simulation_ordering_step_id');
            $table->foreign('simulation_ordering_step_id', 'simulation_ordering_answers_simulation_ordering_step_id_foreign')->references('id')->on('simulation_ordering_steps')->onDelete('cascade');
            $table->unsignedSmallInteger('submitted_position');
            $table->boolean('is_correct');
            $table->timestamps();
            $table->unique(['simulation_attempt_id', 'simulation_ordering_step_id'], 'sim_ordering_answers_attempt_step_unique');
            $table->index(['simulation_ordering_step_id'], 'simulation_ordering_answers_simulation_ordering_step_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_ordering_answers');
    }
};
