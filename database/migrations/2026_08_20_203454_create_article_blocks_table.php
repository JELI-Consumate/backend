<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_blocks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('article_content_id')->constrained('article_contents')->cascadeOnDelete();
            $table->string('block_type');
            $table->text('text_article')->nullable();
            $table->string('image_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_content_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_blocks');
    }
};
