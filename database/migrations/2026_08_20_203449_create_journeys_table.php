<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectors')->cascadeOnDelete();
            $table->string('slug', 100);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->smallInteger('order')->unsigned();
            $table->smallInteger('estimated_minutes')->unsigned()->default(0)->comment('turunan (BR-13), direkalkulasi ModuleObserver');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sector_id', 'slug']);
            $table->index(['sector_id', 'status', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journeys');
    }
};
