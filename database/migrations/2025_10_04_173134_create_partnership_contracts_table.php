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
        Schema::create('partnership_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('contract_number', 100)->unique();
            $table->year('contract_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            // Indexes
            $table->index('contract_number');
            $table->index('contract_year');
            $table->index(['client_id', 'contract_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partnership_contracts');
    }
};
