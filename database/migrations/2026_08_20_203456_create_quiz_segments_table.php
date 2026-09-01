<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_segments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_content_id')->constrained('quiz_contents')->cascadeOnDelete();
            $table->string('segment_type');
            $table->string('title', 200);
            $table->text('instruction')->nullable();
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['quiz_content_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_segments');
    }
};
