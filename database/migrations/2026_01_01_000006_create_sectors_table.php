<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 100);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('pretest_survey_link', 2048)->nullable();
            $table->string('posttest_survey_link', 2048)->nullable();
            $table->unsignedSmallInteger('order');
            $table->boolean('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['is_active', 'order'], 'sectors_is_active_order_index');
            $table->unique(['slug'], 'sectors_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};
