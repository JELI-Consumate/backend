<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_ordering_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('simulation_attempt_id')->constrained('simulation_attempts')->cascadeOnDelete();
            $table->foreignId('simulation_ordering_step_id')->constrained('simulation_ordering_steps')->cascadeOnDelete();
            $table->unsignedSmallInteger('submitted_position');
            $table->boolean('is_correct');
            $table->timestamps();

            $table->unique(['simulation_attempt_id', 'simulation_ordering_step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_ordering_answers');
    }
};
