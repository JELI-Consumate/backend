<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_questions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('reflection_section_id')->constrained('reflection_sections')->cascadeOnDelete();
            $table->string('question_type');
            $table->text('question_text');
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reflection_section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_questions');
    }
};
