<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reflection_question_id')->constrained('reflection_questions')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reflection_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_entries');
    }
};
