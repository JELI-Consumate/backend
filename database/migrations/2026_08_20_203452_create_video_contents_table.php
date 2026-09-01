<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_contents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('youtube_url');
            $table->text('prompt_question')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_contents');
    }
};
