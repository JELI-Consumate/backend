<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journeys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('sector_id');
            $table->foreign('sector_id', 'journeys_sector_id_foreign')->references('id')->on('sectors')->onDelete('cascade');
            $table->string('slug', 100);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedSmallInteger('order');
            $table->unsignedSmallInteger('estimated_minutes')->default(0)->comment('turunan (BR-13), direkalkulasi ModuleObserver');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['sector_id', 'slug'], 'journeys_sector_id_slug_unique');
            $table->index(['sector_id', 'status', 'order'], 'journeys_sector_id_status_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journeys');
    }
};
