<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_sections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('reflection_content_id');
            $table->foreign('reflection_content_id', 'reflection_sections_reflection_content_id_foreign')->references('id')->on('reflection_contents')->onDelete('cascade');
            $table->string('title', 200);
            $table->text('instruction')->nullable();
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['reflection_content_id', 'order'], 'reflection_sections_reflection_content_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_sections');
    }
};
