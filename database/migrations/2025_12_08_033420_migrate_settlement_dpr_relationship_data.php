<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy generated_payment_request_id from settlements to settlement_id in daily_payment_requests
        DB::statement('
            UPDATE daily_payment_requests dpr
            INNER JOIN settlements s ON s.generated_payment_request_id = dpr.id
            SET dpr.settlement_id = s.id
            WHERE s.generated_payment_request_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear settlement_id in daily_payment_requests
        DB::table('daily_payment_requests')
            ->whereNotNull('settlement_id')
            ->update(['settlement_id' => null]);
    }
};
