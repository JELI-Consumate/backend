<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_matching_answers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('simulation_attempt_id')->constrained('simulation_attempts')->cascadeOnDelete();
            $table->foreignUlid('simulation_matching_pair_id')->constrained('simulation_matching_pairs')->cascadeOnDelete();
            $table->foreignUlid('submitted_right_pair_id')->constrained('simulation_matching_pairs')->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->timestamps();

            $table->unique(['simulation_attempt_id', 'simulation_matching_pair_id'], 'sim_matching_answers_attempt_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_matching_answers');
    }
};
