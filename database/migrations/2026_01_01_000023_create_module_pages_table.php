<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('module_id');
            $table->foreign('module_id', 'module_pages_module_id_foreign')->references('id')->on('modules')->onDelete('cascade');
            $table->unsignedSmallInteger('order');
            $table->string('contentable_type', 50);
            $table->ulid('contentable_id');
            $table->timestamps();
            $table->index(['contentable_type', 'contentable_id'], 'module_pages_contentable_type_contentable_id_index');
            $table->index(['module_id', 'order'], 'module_pages_module_id_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_pages');
    }
};
