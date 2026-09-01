<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('quiz_content_id')->constrained('quiz_contents')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->unsignedSmallInteger('choice_score')->nullable();
            $table->unsignedSmallInteger('choice_max_score')->nullable();
            $table->boolean('passed')->nullable();
            $table->decimal('likert_average', 4, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'quiz_content_id', 'attempt_number']);
            $table->index(['user_id', 'quiz_content_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
