<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('article_content_id');
            $table->foreign('article_content_id', 'article_blocks_article_content_id_foreign')->references('id')->on('article_contents')->onDelete('cascade');
            $table->string('block_type');
            $table->text('text_article')->nullable();
            $table->string('image_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['article_content_id', 'order'], 'article_blocks_article_content_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_blocks');
    }
};
