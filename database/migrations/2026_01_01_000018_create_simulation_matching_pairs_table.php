<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_matching_pairs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('simulation_content_id');
            $table->foreign('simulation_content_id', 'simulation_matching_pairs_simulation_content_id_foreign')->references('id')->on('simulation_contents')->onDelete('cascade');
            $table->text('left_label');
            $table->text('left_description')->nullable();
            $table->string('left_image_url')->nullable();
            $table->text('right_label');
            $table->text('right_description')->nullable();
            $table->string('right_image_url')->nullable();
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['simulation_content_id', 'order'], 'simulation_matching_pairs_simulation_content_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_matching_pairs');
    }
};
