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
        Schema::create('program_activity_items', function (Blueprint $table) {
            $table->id();
            // $table->string('code')->unique();
            $table->foreignId('program_activity_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->unsignedInteger('volume');
            $table->string('unit'); // Satuan
            $table->unsignedInteger('frequency')->default(1);
            $table->decimal('total_item_budget', 15, 2)->default(0);
            $table->decimal('total_item_planned_budget', 15, 2)->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_activity_items');
    }
};
