<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reflection_question_id')->constrained('reflection_questions')->cascadeOnDelete();
            $table->text('label');
            $table->smallInteger('order')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reflection_question_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_checklist_items');
    }
};
