<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_agency_channels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('complaint_agency_id')->constrained('complaint_agencies')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('label', 150);
            $table->string('value', 2048);
            $table->string('button_label', 100)->nullable();
            $table->unsignedSmallInteger('order');
            $table->timestamps();
            $table->index(['complaint_agency_id', 'order'], 'complaint_agency_channels_agency_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_agency_channels');
    }
};
