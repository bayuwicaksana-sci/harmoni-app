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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('internal_id', 20)->unique();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('job_title_id')->constrained()->onDelete('restrict');
            $table->foreignId('job_grade_id')->constrained()->restrictOnDelete();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable()->unique();
            $table->string('bank_cust_name')->nullable();
            $table->timestamps();

            $table->index('internal_id');
            $table->index('supervisor_id');
            $table->index('user_id');
            $table->index('job_title_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
