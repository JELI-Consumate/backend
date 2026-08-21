<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 200);
            $table->string('simulation_type');
            $table->text('scenario');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_contents');
    }
};
