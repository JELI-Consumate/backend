<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id('id');
            $table->timestamp('completed_at')->nullable();
            $table->string('file_disk');
            $table->string('file_name')->nullable();
            $table->string('exporter');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->ulid('user_id');
            $table->foreign('user_id', 'exports_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
            $table->index(['user_id'], 'exports_user_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
