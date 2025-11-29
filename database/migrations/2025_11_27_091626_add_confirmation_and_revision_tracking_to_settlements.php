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
        Schema::table('settlements', function (Blueprint $table) {
            $table->foreignId('confirmed_by')
                ->nullable()
                ->after('refund_confirmed_at')
                ->constrained('employees', 'id')
                ->nullOnDelete();

            $table->timestamp('confirmed_at')
                ->nullable()
                ->after('confirmed_by');

            $table->text('revision_notes')
                ->nullable()
                ->after('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_by', 'confirmed_at', 'revision_notes']);
        });
    }
};
