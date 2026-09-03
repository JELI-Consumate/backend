<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('journey_id');
            $table->foreign('journey_id', 'badges_journey_id_foreign')->references('id')->on('journeys')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description');
            $table->text('congratulation_message')->nullable();
            $table->text('motivational_message')->nullable();
            $table->string('icon_url');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['journey_id'], 'badges_journey_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
