<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance daily_operations with overhead cost and reversal tracking
        Schema::table('daily_operations', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_operations', 'total_overhead_cost')) {
                $table->decimal('total_overhead_cost', 15, 2)->default(0)->after('total_labor_cost');
            }
            if (!Schema::hasColumn('daily_operations', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('stock_posted_at');
            }
            if (!Schema::hasColumn('daily_operations', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            }
        });

        // 2. Create manufacturing_overhead_allocations table
        if (!Schema::hasTable('manufacturing_overhead_allocations')) {
            Schema::create('manufacturing_overhead_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('daily_operation_id')->constrained('daily_operations')->cascadeOnDelete();
                $table->foreignId('expense_ledger_id')->nullable()->constrained('ledgers')->nullOnDelete();
                $table->string('overhead_category', 100); // electricity, rent, machinery_maintenance, indirect_labor, consumables, general
                $table->string('allocation_method', 50)->default('direct'); // direct, production_quantity, direct_labor_hours, machine_hours
                $table->decimal('allocation_basis_value', 15, 4)->default(0);
                $table->decimal('rate_per_unit', 15, 4)->default(0);
                $table->decimal('allocated_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Enhance ledgers table with explicit inventory classification
        Schema::table('ledgers', function (Blueprint $table) {
            if (!Schema::hasColumn('ledgers', 'is_inventory')) {
                $table->boolean('is_inventory')->default(false)->after('is_bank_or_cash');
            }
            if (!Schema::hasColumn('ledgers', 'inventory_type')) {
                $table->string('inventory_type', 50)->nullable()->after('is_inventory'); // raw_materials, finished_goods, general, packaging
            }
        });

        // 4. Enhance payrolls with direct manufacturing labor settlement tracking
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'manufacturing_labor_settled')) {
                $table->decimal('manufacturing_labor_settled', 15, 2)->default(0)->after('net_salary');
            }
            if (!Schema::hasColumn('payrolls', 'administrative_salary_expense')) {
                $table->decimal('administrative_salary_expense', 15, 2)->default(0)->after('manufacturing_labor_settled');
            }
        });

        // 5. Backfill unambiguous existing inventory asset ledgers
        DB::table('ledgers')
            ->whereIn('name', ['Stock on Hand', 'Raw Materials Inventory', 'Finished Goods Inventory'])
            ->update([
                'is_inventory' => true,
                'inventory_type' => DB::raw("CASE 
                    WHEN name = 'Raw Materials Inventory' THEN 'raw_materials' 
                    WHEN name = 'Finished Goods Inventory' THEN 'finished_goods' 
                    ELSE 'general' 
                END")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('manufacturing_overhead_allocations')) {
            Schema::dropIfExists('manufacturing_overhead_allocations');
        }

        Schema::table('daily_operations', function (Blueprint $table) {
            if (Schema::hasColumn('daily_operations', 'reversed_by')) {
                $table->dropForeign(['reversed_by']);
                $table->dropColumn('reversed_by');
            }
            if (Schema::hasColumn('daily_operations', 'reversed_at')) {
                $table->dropColumn('reversed_at');
            }
            if (Schema::hasColumn('daily_operations', 'total_overhead_cost')) {
                $table->dropColumn('total_overhead_cost');
            }
        });

        Schema::table('ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('ledgers', 'inventory_type')) {
                $table->dropColumn('inventory_type');
            }
            if (Schema::hasColumn('ledgers', 'is_inventory')) {
                $table->dropColumn('is_inventory');
            }
        });

        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'administrative_salary_expense')) {
                $table->dropColumn('administrative_salary_expense');
            }
            if (Schema::hasColumn('payrolls', 'manufacturing_labor_settled')) {
                $table->dropColumn('manufacturing_labor_settled');
            }
        });
    }
};
