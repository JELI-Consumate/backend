<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * question_type=checklist sekarang multi-item (lihat reflection_checklist_items/
 * reflection_checklist_answers) — is_checked tunggal per pertanyaan di
 * reflection_entries jadi tidak relevan lagi. reflection_entries murni untuk
 * jawaban open_question (answer_text) setelah ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reflection_entries', function (Blueprint $table): void {
            $table->dropColumn('is_checked');
        });
    }

    public function down(): void
    {
        Schema::table('reflection_entries', function (Blueprint $table): void {
            $table->boolean('is_checked')->nullable();
        });
    }
};
