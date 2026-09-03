<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likert_scale_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('quiz_segment_id');
            $table->foreign('quiz_segment_id', 'likert_scale_options_quiz_segment_id_foreign')->references('id')->on('quiz_segments')->onDelete('cascade');
            $table->unsignedTinyInteger('value');
            $table->string('label', 100);
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['quiz_segment_id', 'order'], 'likert_scale_options_quiz_segment_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likert_scale_options');
    }
};
