<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_choice_answers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignUlid('quiz_question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->foreignUlid('quiz_choice_option_id')->constrained('quiz_choice_options')->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'quiz_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_choice_answers');
    }
};
