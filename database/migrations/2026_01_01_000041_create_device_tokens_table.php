<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id', 'device_tokens_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            $table->string('fcm_token');
            $table->string('platform', 10);
            $table->timestamps();
            $table->unique(['fcm_token'], 'device_tokens_fcm_token_unique');
            $table->index(['user_id'], 'device_tokens_user_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
