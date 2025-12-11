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
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropForeign(['generated_payment_request_id']);
            $table->dropColumn('generated_payment_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->foreignId('generated_payment_request_id')
                ->nullable()
                ->after('status')
                ->constrained('daily_payment_requests')
                ->nullOnDelete();
        });
    }
};
