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
        if (Schema::hasTable('daily_operations') && !Schema::hasColumn('daily_operations', 'journal_entry_id')) {
            Schema::table('daily_operations', function (Blueprint $table) {
                $table->foreignId('journal_entry_id')
                    ->nullable()
                    ->after('stock_posted')
                    ->constrained('journal_entries')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('stock_adjustments') && !Schema::hasColumn('stock_adjustments', 'journal_entry_id')) {
            Schema::table('stock_adjustments', function (Blueprint $table) {
                $table->foreignId('journal_entry_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('journal_entries')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('daily_operations') && Schema::hasColumn('daily_operations', 'journal_entry_id')) {
            Schema::table('daily_operations', function (Blueprint $table) {
                $table->dropForeign(['journal_entry_id']);
                $table->dropColumn('journal_entry_id');
            });
        }

        if (Schema::hasTable('stock_adjustments') && Schema::hasColumn('stock_adjustments', 'journal_entry_id')) {
            Schema::table('stock_adjustments', function (Blueprint $table) {
                $table->dropForeign(['journal_entry_id']);
                $table->dropColumn('journal_entry_id');
            });
        }
    }
};
