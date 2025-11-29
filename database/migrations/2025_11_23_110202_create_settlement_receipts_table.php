<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settlement_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('realization_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_receipts');
    }
};
