<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->smallInteger('order')->unsigned();
            $table->string('contentable_type', 50);
            $table->unsignedBigInteger('contentable_id');
            $table->timestamps();

            $table->index(['module_id', 'order']);
            $table->index(['contentable_type', 'contentable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_pages');
    }
};
