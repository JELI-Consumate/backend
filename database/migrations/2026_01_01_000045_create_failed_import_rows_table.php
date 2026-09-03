<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_import_rows', function (Blueprint $table) {
            $table->id('id');
            $table->json('data');
            $table->unsignedBigInteger('import_id');
            $table->foreign('import_id', 'failed_import_rows_import_id_foreign')->references('id')->on('imports')->onDelete('cascade');
            $table->text('validation_error')->nullable();
            $table->timestamps();
            $table->index(['import_id'], 'failed_import_rows_import_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
    }
};
