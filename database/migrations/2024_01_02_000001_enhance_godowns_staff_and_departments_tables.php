<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance godowns (Warehouses, Vans, Staging Locations)
        Schema::table('godowns', function (Blueprint $table) {
            if (!Schema::hasColumn('godowns', 'location_type')) {
                $table->string('location_type', 50)->default('warehouse')->after('code'); // warehouse, production_unit, sales_outlet, van_mobile, packing_unit, project_site, other
            }
            if (!Schema::hasColumn('godowns', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('branch_id')->constrained('vehicles')->nullOnDelete();
            }
            if (!Schema::hasColumn('godowns', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('vehicle_id')->constrained('departments')->nullOnDelete();
            }
        });

        // 2. Enhance staff (Reporting hierarchy, piece-rate, hourly wage, contract)
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'role_position')) {
                $table->string('role_position', 50)->default('staff')->after('designation'); // department_head, supervisor, team_leader, worker, staff
            }
            if (!Schema::hasColumn('staff', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('department_id')->constrained('staff')->nullOnDelete();
            }
            if (!Schema::hasColumn('staff', 'hourly_rate')) {
                $table->decimal('hourly_rate', 15, 2)->default(0)->after('salary_amount');
            }
            if (!Schema::hasColumn('staff', 'piece_rate')) {
                $table->decimal('piece_rate', 15, 2)->default(0)->after('hourly_rate');
            }
        });

        // Expand employee_type enum on MySQL safely
        DB::statement("ALTER TABLE staff MODIFY COLUMN employee_type ENUM('salaried', 'daily_wage', 'hourly', 'contract', 'piece_rate') NOT NULL DEFAULT 'salaried'");

        // 3. Enhance departments (Hierarchical, typed departments, head & supervisor links)
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'department_type')) {
                $table->string('department_type', 50)->default('general')->after('code'); // production, packing, quality_control, cutting, stitching, sales, warehouse, general
            }
            if (!Schema::hasColumn('departments', 'parent_department_id')) {
                $table->foreignId('parent_department_id')->nullable()->after('branch_id')->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'head_staff_id')) {
                $table->foreignId('head_staff_id')->nullable()->after('department_head')->constrained('staff')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'supervisor_staff_id')) {
                $table->foreignId('supervisor_staff_id')->nullable()->after('head_staff_id')->constrained('staff')->nullOnDelete();
            }
        });

        // 4. Enhance stock_transfers (Dispatch & Receipt Confirmation tracking)
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'dispatched_by')) {
                $table->foreignId('dispatched_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable()->after('dispatched_by');
            }
            if (!Schema::hasColumn('stock_transfers', 'received_by')) {
                $table->foreignId('received_by')->nullable()->after('dispatched_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['dispatched_by']);
            $table->dropForeign(['received_by']);
            $table->dropColumn(['dispatched_by', 'dispatched_at', 'received_by', 'received_at']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['parent_department_id']);
            $table->dropForeign(['head_staff_id']);
            $table->dropForeign(['supervisor_staff_id']);
            $table->dropColumn(['department_type', 'parent_department_id', 'head_staff_id', 'supervisor_staff_id']);
        });

        DB::statement("ALTER TABLE staff MODIFY COLUMN employee_type ENUM('salaried', 'daily_wage') NOT NULL DEFAULT 'salaried'");

        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['role_position', 'supervisor_id', 'hourly_rate', 'piece_rate']);
        });

        Schema::table('godowns', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['location_type', 'vehicle_id', 'department_id']);
        });
    }
};

