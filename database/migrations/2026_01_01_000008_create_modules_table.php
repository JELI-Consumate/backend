<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('journey_id');
            $table->foreign('journey_id', 'modules_journey_id_foreign')->references('id')->on('journeys')->onDelete('cascade');
            $table->string('type');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(5);
            $table->unsignedSmallInteger('order');
            $table->boolean('is_required')->default(1);
            $table->string('status')->default('draft');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['journey_id', 'status', 'order'], 'modules_journey_id_status_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
