<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_choice_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('quiz_question_id');
            $table->foreign('quiz_question_id', 'quiz_choice_options_quiz_question_id_foreign')->references('id')->on('quiz_questions')->onDelete('cascade');
            $table->text('option_text');
            $table->boolean('is_correct')->default(0);
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['quiz_question_id', 'order'], 'quiz_choice_options_quiz_question_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_choice_options');
    }
};
