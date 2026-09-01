<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_choice_options', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['quiz_question_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_choice_options');
    }
};
