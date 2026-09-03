<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'reflection_entries_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('reflection_question_id');
            $table->foreign('reflection_question_id', 'reflection_entries_reflection_question_id_foreign')->references('id')->on('reflection_questions')->onDelete('cascade');
            $table->text('answer_text')->nullable();
            $table->timestamps();
            $table->index(['reflection_question_id'], 'reflection_entries_reflection_question_id_foreign');
            $table->unique(['user_id', 'reflection_question_id'], 'reflection_entries_user_id_reflection_question_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_entries');
    }
};
