<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 200);
            $table->text('opening_message');
            $table->string('closing_title')->nullable();
            $table->text('closing_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_contents');
    }
};
