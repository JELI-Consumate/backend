<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_checklist_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'reflection_checklist_answers_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('reflection_checklist_item_id');
            $table->foreign('reflection_checklist_item_id', 'refl_checklist_answers_item_fk')->references('id')->on('reflection_checklist_items')->onDelete('cascade');
            $table->boolean('is_checked')->default(0);
            $table->timestamps();
            $table->index(['reflection_checklist_item_id'], 'refl_checklist_answers_item_fk');
            $table->unique(['user_id', 'reflection_checklist_item_id'], 'refl_checklist_answers_user_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_checklist_answers');
    }
};
