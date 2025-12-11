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
        Schema::table('daily_payment_requests', function (Blueprint $table) {
            $table->foreignId('settlement_id')
                ->nullable()
                ->after('requester_id')
                ->constrained('settlements')
                ->nullOnDelete();

            $table->index('settlement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_payment_requests', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
            $table->dropIndex(['settlement_id']);
            $table->dropColumn('settlement_id');
        });
    }
};
