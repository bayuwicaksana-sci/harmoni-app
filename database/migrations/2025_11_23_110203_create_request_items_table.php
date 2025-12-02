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
        Schema::create('request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_payment_request_id')->constrained('daily_payment_requests')->onDelete('cascade');
            $table->foreignId('coa_id')->nullable()->constrained('coas')->nullOnDelete();
            $table->foreignId('program_activity_id')->nullable()->constrained('program_activities')->nullOnDelete();
            $table->foreignId('program_activity_item_id')->nullable()->constrained('program_activity_items')->nullOnDelete();
            $table->string('payment_type')->nullable();
            $table->decimal('advance_percentage', 5, 2)->nullable();
            $table->foreignId('request_item_type_id')->nullable()->constrained('request_item_types')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->text('unit_quantity')->nullable();
            $table->decimal('amount_per_item', 15, 2)->nullable();
            $table->decimal('act_quantity', 15, 2)->nullable();
            $table->decimal('act_amount_per_item', 15, 2)->nullable();
            $table->string('tax_method')->nullable();
            // $table->decimal('amount', 15, 2)->nullable();
            // $table->decimal('tax_amount', 15, 2)->nullable();
            // $table->decimal('net_amount', 15, 2)->nullable();
            $table->boolean('self_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('account_owner')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_taxed')->default(false);
            $table->boolean('is_unplanned')->default(false);
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('settling_for')->nullable()->constrained('request_items', 'id')->cascadeOnDelete();

            // Snapshot fields
            $table->string('coa_code', 100)->nullable();
            $table->string('coa_name', 255)->nullable();
            $table->string('coa_type')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->string('program_name', 255)->nullable();
            $table->string('program_code', 100)->nullable();
            $table->string('program_category_name', 100)->nullable();
            // $table->unsignedBigInteger('program_series_id')->nullable();
            // $table->string('program_series_name', 255)->nullable();
            $table->year('contract_year')->nullable();
            $table->string('tax_type', 50)->nullable();
            $table->decimal('tax_rate', 5, 4)->nullable();
            $table->string('item_type_name', 100)->nullable();

            $table->date('due_date')->nullable();
            $table->date('realization_date')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('payment_type');
            $table->index('daily_payment_request_id');
            $table->index('coa_id');
            $table->index('program_activity_id');
            $table->index('program_activity_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_items');
    }
};
