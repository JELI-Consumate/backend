<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('quiz_segment_id');
            $table->foreign('quiz_segment_id', 'quiz_questions_quiz_segment_id_foreign')->references('id')->on('quiz_segments')->onDelete('cascade');
            $table->text('question');
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['quiz_segment_id', 'order'], 'quiz_questions_quiz_segment_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
