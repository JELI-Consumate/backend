<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_choice_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('quiz_attempt_id');
            $table->foreign('quiz_attempt_id', 'quiz_choice_answers_quiz_attempt_id_foreign')->references('id')->on('quiz_attempts')->onDelete('cascade');
            $table->ulid('quiz_question_id');
            $table->foreign('quiz_question_id', 'quiz_choice_answers_quiz_question_id_foreign')->references('id')->on('quiz_questions')->onDelete('cascade');
            $table->ulid('quiz_choice_option_id');
            $table->foreign('quiz_choice_option_id', 'quiz_choice_answers_quiz_choice_option_id_foreign')->references('id')->on('quiz_choice_options')->onDelete('cascade');
            $table->boolean('is_correct');
            $table->timestamps();
            $table->unique(['quiz_attempt_id', 'quiz_question_id'], 'quiz_choice_answers_quiz_attempt_id_quiz_question_id_unique');
            $table->index(['quiz_choice_option_id'], 'quiz_choice_answers_quiz_choice_option_id_foreign');
            $table->index(['quiz_question_id'], 'quiz_choice_answers_quiz_question_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_choice_answers');
    }
};
