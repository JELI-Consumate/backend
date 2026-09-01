<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_contents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('kind');
            $table->foreignUlid('journey_id')->nullable()->constrained('journeys')->cascadeOnDelete();
            $table->foreignUlid('sector_id')->nullable()->constrained('sectors')->cascadeOnDelete();
            $table->tinyInteger('passing_score')->unsigned()->default(70);
            $table->boolean('shuffle_questions')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kind', 'journey_id']);
            $table->index(['kind', 'sector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_contents');
    }
};
