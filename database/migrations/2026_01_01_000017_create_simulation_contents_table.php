<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_contents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title', 200);
            $table->string('simulation_type');
            $table->text('scenario');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_contents');
    }
};
