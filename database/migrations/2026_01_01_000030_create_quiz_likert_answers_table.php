<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_likert_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('quiz_attempt_id');
            $table->foreign('quiz_attempt_id', 'quiz_likert_answers_quiz_attempt_id_foreign')->references('id')->on('quiz_attempts')->onDelete('cascade');
            $table->ulid('quiz_question_id');
            $table->foreign('quiz_question_id', 'quiz_likert_answers_quiz_question_id_foreign')->references('id')->on('quiz_questions')->onDelete('cascade');
            $table->ulid('likert_scale_option_id');
            $table->foreign('likert_scale_option_id', 'quiz_likert_answers_likert_scale_option_id_foreign')->references('id')->on('likert_scale_options')->onDelete('cascade');
            $table->timestamps();
            $table->index(['likert_scale_option_id'], 'quiz_likert_answers_likert_scale_option_id_foreign');
            $table->unique(['quiz_attempt_id', 'quiz_question_id'], 'quiz_likert_answers_quiz_attempt_id_quiz_question_id_unique');
            $table->index(['quiz_question_id'], 'quiz_likert_answers_quiz_question_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_likert_answers');
    }
};
