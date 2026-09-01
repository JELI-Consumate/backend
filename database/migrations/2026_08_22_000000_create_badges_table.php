<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('journey_id')->index()->constrained('journeys')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description');
            $table->text('congratulation_message')->nullable();
            $table->text('motivational_message')->nullable();
            $table->string('icon_url');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
