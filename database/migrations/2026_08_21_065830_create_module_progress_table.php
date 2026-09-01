<?php

declare(strict_types=1);

use App\Enums\ProgressStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom `module_page_id` merujuk ke module_pages.id (bukan id konten polimorfik) —
     * lihat 03-model-data.md §F, keputusan pertanyaan terbuka #1 (rename dari `content_id`).
     */
    public function up(): void
    {
        Schema::create('module_progress', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('module_page_id')->constrained('module_pages')->cascadeOnDelete();
            $table->string('status', 20)->default(ProgressStatus::NotStarted->value);
            $table->unsignedSmallInteger('last_position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'module_page_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_progress');
    }
};
