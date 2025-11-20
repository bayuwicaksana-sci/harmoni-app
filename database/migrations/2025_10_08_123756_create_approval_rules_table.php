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
        Schema::create('approval_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->tinyInteger('sequence')->unsigned();
            $table->string('condition_type');
            $table->decimal('condition_value', 15, 2)->nullable();
            $table->string('approver_type')->default('supervisor');
            $table->foreignId('approver_job_level_id')->nullable()->constrained('job_levels')->onDelete('cascade');
            $table->foreignId('approver_job_title_id')->nullable()->constrained('job_titles')->onDelete('cascade');

            $table->timestamps();

            // Add index
            $table->index('approver_type');
            $table->index('approver_job_title_id');
            $table->index(['approval_workflow_id', 'sequence']);
            $table->index('approver_job_level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_rules');
    }
};
