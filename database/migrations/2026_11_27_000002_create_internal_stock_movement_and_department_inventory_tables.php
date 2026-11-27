<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Branch stock configuration
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'stock_mode')) {
                $table->string('stock_mode', 50)->default('centralized')->after('is_main_branch');
            }
            if (!Schema::hasColumn('branches', 'central_godown_id')) {
                $table->foreignId('central_godown_id')->nullable()->after('stock_mode')->constrained('godowns')->nullOnDelete();
            }
        });

        // 2. Department stock configuration
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'maintains_stock')) {
                $table->boolean('maintains_stock')->default(false)->after('department_type');
            }
            if (!Schema::hasColumn('departments', 'default_godown_id')) {
                $table->foreignId('default_godown_id')->nullable()->after('maintains_stock')->constrained('godowns')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'source_godown_id')) {
                $table->foreignId('source_godown_id')->nullable()->after('default_godown_id')->constrained('godowns')->nullOnDelete();
            }
        });

        // 3. Stock Requests Enhancement
        Schema::table('stock_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_requests', 'from_godown_id')) {
                $table->foreignId('from_godown_id')->nullable()->after('to_department_id')->constrained('godowns')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_requests', 'source_godown_id')) {
                $table->foreignId('source_godown_id')->nullable()->after('from_godown_id')->constrained('godowns')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_requests', 'required_date')) {
                $table->date('required_date')->nullable()->after('request_date');
            }
            if (!Schema::hasColumn('stock_requests', 'priority')) {
                $table->string('priority', 30)->default('medium')->after('required_date');
            }
            if (!Schema::hasColumn('stock_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('stock_requests', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Modify stock_requests.status to varchar(50) for rich state machine
        DB::statement("ALTER TABLE stock_requests MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'requested'");

        // 4. Stock Request Items Enhancement
        Schema::table('stock_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_request_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('item_id')->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_request_items', 'approved_qty')) {
                $table->decimal('approved_qty', 15, 3)->default(0)->after('requested_qty');
            }
            if (!Schema::hasColumn('stock_request_items', 'dispatched_qty')) {
                $table->decimal('dispatched_qty', 15, 3)->default(0)->after('approved_qty');
            }
            if (!Schema::hasColumn('stock_request_items', 'received_qty')) {
                $table->decimal('received_qty', 15, 3)->default(0)->after('dispatched_qty');
            }
            if (!Schema::hasColumn('stock_request_items', 'preferred_batch_id')) {
                $table->foreignId('preferred_batch_id')->nullable()->after('received_qty')->constrained('stock_batches')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_request_items', 'remarks')) {
                $table->text('remarks')->nullable()->after('preferred_batch_id');
            }
        });

        // 5. Stock Transfers Enhancement
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'stock_request_id')) {
                $table->foreignId('stock_request_id')->nullable()->after('company_id')->constrained('stock_requests')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'dispatch_reference')) {
                $table->string('dispatch_reference', 100)->nullable()->after('transfer_no');
            }
            if (!Schema::hasColumn('stock_transfers', 'receiver_remarks')) {
                $table->text('receiver_remarks')->nullable()->after('remarks');
            }
        });

        // Modify stock_transfers.status to varchar(50) for rich lifecycle
        DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");

        // 6. Stock Transfer Items Enhancement
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfer_items', 'stock_request_item_id')) {
                $table->foreignId('stock_request_item_id')->nullable()->after('stock_transfer_id')->constrained('stock_request_items')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfer_items', 'dispatched_qty')) {
                $table->decimal('dispatched_qty', 15, 3)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'received_qty')) {
                $table->decimal('received_qty', 15, 3)->default(0)->after('dispatched_qty');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'damaged_qty')) {
                $table->decimal('damaged_qty', 15, 3)->default(0)->after('received_qty');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'shortage_qty')) {
                $table->decimal('shortage_qty', 15, 3)->default(0)->after('damaged_qty');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'in_transit_qty')) {
                $table->decimal('in_transit_qty', 15, 3)->default(0)->after('shortage_qty');
            }
        });

        // Populate dispatched_qty from quantity for existing rows
        DB::statement("UPDATE stock_transfer_items SET dispatched_qty = quantity WHERE dispatched_qty = 0 AND quantity > 0");

        // 7. Create stock_discrepancies table
        if (!Schema::hasTable('stock_discrepancies')) {
            Schema::create('stock_discrepancies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
                $table->foreignId('stock_transfer_item_id')->nullable()->constrained('stock_transfer_items')->nullOnDelete();
                $table->foreignId('stock_request_id')->nullable()->constrained('stock_requests')->nullOnDelete();
                $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
                $table->foreignId('batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
                $table->foreignId('source_godown_id')->constrained('godowns')->cascadeOnDelete();
                $table->foreignId('dest_godown_id')->constrained('godowns')->cascadeOnDelete();
                $table->decimal('dispatched_qty', 15, 3);
                $table->decimal('received_qty', 15, 3);
                $table->decimal('discrepancy_qty', 15, 3);
                $table->string('discrepancy_type', 50); // shortage, damaged_goods, wrong_item, wrong_batch, quality_rejection, excess_quantity, other
                $table->text('reason');
                $table->text('remarks')->nullable();
                $table->string('status', 50)->default('reported'); // reported, under_investigation, pending_additional_dispatch, pending_approval, resolved, cancelled
                $table->text('investigation_remarks')->nullable();
                $table->foreignId('investigated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('investigated_at')->nullable();
                $table->string('resolution_type', 50)->nullable(); // additional_dispatch, separate_delivery, loss_write_off, cancel_outstanding
                $table->text('resolution_notes')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('stock_adjustment_id')->nullable()->constrained('stock_adjustments')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['stock_transfer_id']);
            });
        }

        // 8. Create stock_discrepancy_approvals table
        if (!Schema::hasTable('stock_discrepancy_approvals')) {
            Schema::create('stock_discrepancy_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_discrepancy_id')->constrained('stock_discrepancies')->cascadeOnDelete();
                $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
                $table->string('action', 50); // submitted, approved, rejected, info_requested
                $table->text('decision_remarks')->nullable();
                $table->decimal('quantity', 15, 3);
                $table->decimal('estimated_value', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // 9. Auto-configure default central godown for existing branches
        $branches = DB::table('branches')->get();
        foreach ($branches as $branch) {
            $firstGodown = DB::table('godowns')
                ->where('company_id', $branch->company_id)
                ->where('branch_id', $branch->id)
                ->where('status', 'active')
                ->first();
            if ($firstGodown) {
                DB::table('branches')
                    ->where('id', $branch->id)
                    ->update(['central_godown_id' => $firstGodown->id]);
            }
        }

        // 10. Auto-configure source_godown_id for existing departments
        $departments = DB::table('departments')->get();
        foreach ($departments as $dept) {
            $branch = DB::table('branches')->where('id', $dept->branch_id)->first();
            $centralGodownId = $branch ? $branch->central_godown_id : null;
            if (!$centralGodownId) {
                $g = DB::table('godowns')->where('company_id', $dept->company_id)->first();
                $centralGodownId = $g ? $g->id : null;
            }

            // Check if department has a linked godown
            $deptGodown = DB::table('godowns')
                ->where('department_id', $dept->id)
                ->first();

            $maintainsStock = ($dept->department_type === 'warehouse' || ($deptGodown && $deptGodown->location_type === 'production_unit'));
            DB::table('departments')
                ->where('id', $dept->id)
                ->update([
                    'maintains_stock' => $maintainsStock,
                    'default_godown_id' => $deptGodown ? $deptGodown->id : ($maintainsStock ? $centralGodownId : null),
                    'source_godown_id' => $centralGodownId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_discrepancy_approvals');
        Schema::dropIfExists('stock_discrepancies');

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['stock_request_item_id']);
            $table->dropColumn(['stock_request_item_id', 'dispatched_qty', 'received_qty', 'damaged_qty', 'shortage_qty', 'in_transit_qty']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['stock_request_id']);
            $table->dropColumn(['stock_request_id', 'dispatch_reference', 'receiver_remarks']);
        });

        Schema::table('stock_request_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['preferred_batch_id']);
            $table->dropColumn(['unit_id', 'approved_qty', 'dispatched_qty', 'received_qty', 'preferred_batch_id', 'remarks']);
        });

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropForeign(['from_godown_id']);
            $table->dropForeign(['source_godown_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['from_godown_id', 'source_godown_id', 'required_date', 'priority', 'rejection_reason', 'approved_by', 'approved_at']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['default_godown_id']);
            $table->dropForeign(['source_godown_id']);
            $table->dropColumn(['maintains_stock', 'default_godown_id', 'source_godown_id']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['central_godown_id']);
            $table->dropColumn(['stock_mode', 'central_godown_id']);
        });
    }
};
