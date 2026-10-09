<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_agency_category', function (Blueprint $table) {
            $table->foreignUlid('complaint_category_id')->constrained('complaint_categories')->cascadeOnDelete();
            $table->foreignUlid('complaint_agency_id')->constrained('complaint_agencies')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->primary(['complaint_category_id', 'complaint_agency_id'], 'complaint_agency_category_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_agency_category');
    }
};
