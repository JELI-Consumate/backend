<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_likert_answers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignUlid('quiz_question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->foreignUlid('likert_scale_option_id')->constrained('likert_scale_options')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'quiz_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_likert_answers');
    }
};
