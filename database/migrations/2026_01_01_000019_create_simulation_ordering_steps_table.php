<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_ordering_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('simulation_content_id');
            $table->foreign('simulation_content_id', 'simulation_ordering_steps_simulation_content_id_foreign')->references('id')->on('simulation_contents')->onDelete('cascade');
            $table->text('label');
            $table->string('image_url')->nullable();
            $table->unsignedSmallInteger('correct_position');
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['simulation_content_id', 'order'], 'simulation_ordering_steps_simulation_content_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_ordering_steps');
    }
};
