<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_checklist_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('reflection_question_id');
            $table->foreign('reflection_question_id', 'reflection_checklist_items_reflection_question_id_foreign')->references('id')->on('reflection_questions')->onDelete('cascade');
            $table->text('label');
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['reflection_question_id', 'order'], 'reflection_checklist_items_reflection_question_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_checklist_items');
    }
};
