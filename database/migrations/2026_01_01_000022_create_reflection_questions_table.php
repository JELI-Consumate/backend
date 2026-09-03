<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('reflection_section_id');
            $table->foreign('reflection_section_id', 'reflection_questions_reflection_section_id_foreign')->references('id')->on('reflection_sections')->onDelete('cascade');
            $table->string('question_type');
            $table->text('question_text');
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['reflection_section_id', 'order'], 'reflection_questions_reflection_section_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_questions');
    }
};
