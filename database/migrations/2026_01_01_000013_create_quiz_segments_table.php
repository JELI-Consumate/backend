<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_segments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('quiz_content_id');
            $table->foreign('quiz_content_id', 'quiz_segments_quiz_content_id_foreign')->references('id')->on('quiz_contents')->onDelete('cascade');
            $table->string('segment_type');
            $table->string('title', 200);
            $table->text('instruction')->nullable();
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['quiz_content_id', 'order'], 'quiz_segments_quiz_content_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_segments');
    }
};
