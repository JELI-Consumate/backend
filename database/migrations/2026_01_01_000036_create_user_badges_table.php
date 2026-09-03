<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'user_badges_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('badge_id');
            $table->foreign('badge_id', 'user_badges_badge_id_foreign')->references('id')->on('badges')->onDelete('cascade');
            $table->timestamp('earned_at');
            $table->timestamps();
            $table->index(['badge_id'], 'user_badges_badge_id_foreign');
            $table->unique(['user_id', 'badge_id'], 'user_badges_user_id_badge_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};
