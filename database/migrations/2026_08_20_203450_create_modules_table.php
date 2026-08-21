<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journey_id')->constrained('journeys')->cascadeOnDelete();
            $table->string('type');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->smallInteger('estimated_minutes')->unsigned()->default(5);
            $table->smallInteger('order')->unsigned();
            $table->boolean('is_required')->default(true);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['journey_id', 'status', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
