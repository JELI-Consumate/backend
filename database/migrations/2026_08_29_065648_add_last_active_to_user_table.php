<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('avatar_url');
            $table->timestamp('last_inactive_notified_at')->nullable()->after('last_active_at');
        });
    }

  
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(
                [
                    'last_active_at',
                    'last_inactive_notified_at',
                ]
            );
        });
    }
};
