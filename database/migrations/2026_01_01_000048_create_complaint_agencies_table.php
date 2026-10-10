<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_agencies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 100);
            $table->string('short_name', 50);
            $table->string('full_name', 255);
            $table->string('summary', 500);
            $table->text('description');
            $table->json('when_to_contact')->nullable();
            $table->text('how_to_access')->nullable();
            $table->text('notes')->nullable();
            $table->string('logo_url')->nullable();
            $table->unsignedSmallInteger('order');
            $table->boolean('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['is_active', 'order'], 'complaint_agencies_is_active_order_index');
            $table->unique(['slug'], 'complaint_agencies_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_agencies');
    }
};
