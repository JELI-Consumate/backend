<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'quiz_attempts_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('quiz_content_id');
            $table->foreign('quiz_content_id', 'quiz_attempts_quiz_content_id_foreign')->references('id')->on('quiz_contents')->onDelete('cascade');
            $table->unsignedSmallInteger('attempt_number');
            $table->unsignedSmallInteger('choice_score')->nullable();
            $table->unsignedSmallInteger('choice_max_score')->nullable();
            $table->boolean('passed')->nullable();
            $table->decimal('likert_average', 4, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['quiz_content_id'], 'quiz_attempts_quiz_content_id_foreign');
            $table->unique(['user_id', 'quiz_content_id', 'attempt_number'], 'quiz_attempts_user_id_quiz_content_id_attempt_number_unique');
            $table->index(['user_id', 'quiz_content_id', 'completed_at'], 'quiz_attempts_user_id_quiz_content_id_completed_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
