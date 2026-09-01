<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_matching_pairs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('simulation_content_id')->constrained('simulation_contents')->cascadeOnDelete();
            $table->text('left_label');
            $table->text('left_description')->nullable();
            $table->string('left_image_url')->nullable();
            $table->text('right_label');
            $table->text('right_description')->nullable();
            $table->string('right_image_url')->nullable();
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['simulation_content_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_matching_pairs');
    }
};
