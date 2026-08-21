<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reflection_content_id')->constrained('reflection_contents')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('instruction')->nullable();
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reflection_content_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_sections');
    }
};
