<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type')->comment('e.g., daily_payment_request, invoice, etc.');
            $table->string('prefix')->comment('e.g., SCI-FIN-PAY');
            $table->enum('reset_period', ['none', 'yearly', 'monthly'])->default('none');
            $table->integer('number_length')->default(6)->comment('Number of digits for sequence');
            $table->integer('year')->nullable();
            $table->integer('month')->nullable();
            $table->integer('last_number')->default(0);
            $table->timestamps();

            // Unique constraint based on reset period
            $table->unique(['document_type', 'year', 'month'], 'unique_sequence');
            $table->index(['document_type', 'prefix'], 'doc_type_prefix_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
