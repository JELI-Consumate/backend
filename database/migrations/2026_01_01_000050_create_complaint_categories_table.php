<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('problem', 500);
            $table->unsignedSmallInteger('order');
            $table->boolean('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['is_active', 'order'], 'complaint_categories_is_active_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_categories');
    }
};
