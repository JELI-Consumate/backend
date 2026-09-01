<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likert_scale_options', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_segment_id')->constrained('quiz_segments')->cascadeOnDelete();
            $table->tinyInteger('value')->unsigned();
            $table->string('label', 100);
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['quiz_segment_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likert_scale_options');
    }
};
