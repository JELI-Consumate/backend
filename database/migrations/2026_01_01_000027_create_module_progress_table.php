<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'module_progress_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('module_page_id');
            $table->foreign('module_page_id', 'module_progress_module_page_id_foreign')->references('id')->on('module_pages')->onDelete('cascade');
            $table->string('status', 20)->default('not_started');
            $table->unsignedSmallInteger('last_position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['module_page_id'], 'module_progress_module_page_id_foreign');
            $table->unique(['user_id', 'module_page_id'], 'module_progress_user_id_module_page_id_unique');
            $table->index(['user_id', 'status'], 'module_progress_user_id_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_progress');
    }
};
