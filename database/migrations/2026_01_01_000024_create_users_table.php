<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('google_id', 50)->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('role')->default('user');
            $table->ulid('sector_id')->nullable();
            $table->foreign('sector_id', 'users_sector_id_foreign')->references('id')->on('sectors')->onDelete('set null');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('last_inactive_notified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->unique(['email'], 'users_email_unique');
            $table->unique(['google_id'], 'users_google_id_unique');
            $table->index(['last_active_at', 'last_inactive_notified_at'], 'users_inactivity_notification_index');
            $table->unique(['phone'], 'users_phone_unique');
            $table->index(['sector_id'], 'users_sector_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
