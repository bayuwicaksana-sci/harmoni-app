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
        Schema::create('daily_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->nullable()->unique();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('request_date')->nullable();
            $table->string('status')->default('draft');
            // $table->decimal('total_amount', 15, 2);
            // $table->decimal('total_tax', 15, 2);
            // $table->decimal('net_amount', 15, 2);
            // $table->string('bank_name');
            // $table->string('bank_account_number');
            // $table->string('bank_cust_name');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('request_number');
            $table->index('status');
            $table->index('request_date');
            $table->index(['requester_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_payment_requests');
    }
};
