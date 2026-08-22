<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_checklist_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reflection_checklist_item_id');
            $table->boolean('is_checked')->default(false);
            $table->timestamps();

            $table->foreign('reflection_checklist_item_id', 'refl_checklist_answers_item_fk')
                ->references('id')->on('reflection_checklist_items')->cascadeOnDelete();

            $table->unique(['user_id', 'reflection_checklist_item_id'], 'refl_checklist_answers_user_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_checklist_answers');
    }
};
