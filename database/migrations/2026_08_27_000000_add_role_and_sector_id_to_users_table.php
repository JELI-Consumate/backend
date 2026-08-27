<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengganti flag boolean is_admin dengan role bertingkat (BR admin panel):
     * user (default, tidak bisa login panel), admin (dibatasi 1 sector lewat
     * sector_id), super_admin (akses semua sector). is_admin=true lama
     * diasumsikan setara super_admin karena sebelumnya tidak ada pembatasan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default(UserRole::User->value)->after('avatar_url');
            $table->foreignId('sector_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        DB::table('users')
            ->where('is_admin', true)
            ->update(['role' => UserRole::SuperAdmin->value]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('avatar_url');
        });

        DB::table('users')
            ->where('role', UserRole::SuperAdmin->value)
            ->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sector_id');
            $table->dropColumn('role');
        });
    }
};
