<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_ordering_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('simulation_content_id')->constrained('simulation_contents')->cascadeOnDelete();
            $table->text('label');
            $table->string('image_url')->nullable();
            $table->smallInteger('correct_position')->unsigned();
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['simulation_content_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_ordering_steps');
    }
};
