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
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_payment_request_id')->constrained('daily_payment_requests')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('employees')->onDelete('restrict');
            $table->tinyInteger('sequence')->unsigned();
            $table->string('action')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['daily_payment_request_id', 'sequence']);
            $table->index(['approver_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
